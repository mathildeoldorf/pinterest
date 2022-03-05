<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();

// Authenticate CSRF token to proceed
_csrf_auth();

if ( ! isset($_POST['keyword']) || empty($_POST['keyword']) ){ _res(400, ['info'=>'keyword is required', 'error'=>__LINE__]); }
if ( strlen($_POST['keyword'] ) < _TERM_MIN_LENGTH ){ _res(400, ['info'=>'Keyword is too short. Min. length is 3 characters.', 'error'=>__LINE__]); }
if ( strlen($_POST['keyword'] ) > _TERM_MAX_LENGTH ){ _res(400, ['info'=>'Keyword is too is too long. Max. length is 50 characters', 'error'=>__LINE__]); }
if( isset($_POST['keyword_ID']) ) { 
    if ( ! filter_var($_POST['keyword_ID'], FILTER_VALIDATE_INT) ){ _res(400, ['info'=>'Invalid format', 'error'=>__LINE__]); }
}
// DB connection
$db = _db();

// Initialize variables to save information about which operation took place
$state = [];

// Initialize variables to save information about how many times, the keyword has been searched 
// in the system and by the user, to use when returning data 
// Initialize them with the value of one, and only update them, if they exist in the system
$count_searches = 1;
$count_searches_search_history = 1;

// Initialize variable to save information about keyword ID
$keyword_ID;

// Check if the keyword has been searched before
try {
    $sql; 
    if( isset($_POST['keyword_ID']) ) { 
        // See if the keyword has been searched for before in the system
        $sql = 'SELECT * FROM tKeyword WHERE cKeyword = :keyword AND nKeywordID = :keyword_ID';
    }
    else{
        // See if the keyword has been searched for before in the system
        $sql = 'SELECT * FROM tKeyword WHERE cKeyword = :keyword';
    }

    $q = $db->prepare($sql);

    $q->bindValue(':keyword', $_POST['keyword']);

    if( isset($_POST['keyword_ID']) ) { 
        $q->bindValue(':keyword_ID', $_POST['keyword_ID']);
    }
    
    $q->execute();
    
    $result = $q->fetch();

    // If the keyword was found, then save the ID to use in the coming queries
    $keyword_ID = $result['nKeywordID'];
    
} catch ( Exception $ex ) {
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}

// Keyword exists / has been searched for in the system
if( $result ){
    // Increment the how many times the keyword has been searched for
    $result['nSearches']++;
    $count_searches = $result['nSearches'];

    // Check if the user has searched for the keyword before
    try {
        $sql =  'SELECT * FROM tSearchHistory 
                WHERE nKeywordID = :keyword_ID 
                AND nUserID = :user_ID';

        $q = $db->prepare($sql);

        $q->bindValue(':keyword_ID', $keyword_ID);
        $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
        
        $q->execute();
        
        $result_search_history = $q->fetch();
        
    } catch ( Exception $ex ) {
        _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
    }

    // Begin the process of committing data to the database - initializing the transaction to preserve integrity
    $db->beginTransaction();

    // TODO: upsert
    // If the user has searched for the keyword before then update the search history
    if( $result_search_history ){
        // Increment the how many times the keyword has been searched for by the user
        $result_search_history['nSearches']++;
        $count_searches_search_history = $result_search_history['nSearches'];

        // Update the information in the users search history
        try {
        $sql =  'UPDATE tSearchHistory SET nSearches = :count_searches 
                WHERE nKeywordID = :keyword_ID 
                AND nUserID = :user_ID';
            $q = $db->prepare($sql);
            $q->bindValue(':keyword_ID', $keyword_ID);
            $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
            $q->bindValue(':count_searches', $count_searches_search_history);

            $q->execute();

            if( !$q->rowCount() ){
                $db->rollback();
                _res(400, ['info'=>'Failed to record keyword', 'error'=>__LINE__]);
            }

            array_push($state, "update search history");

        } catch (Exception $ex) {
            // Failed to update the keyword into the database so we rollback any changes
            $db->rollback();
            _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
        }

    }
    else {
        try {
            // Insert keyword into search history
            $sql = 'INSERT INTO tSearchHistory (nKeywordID, nUserID) VALUES (:keyword_ID, :user_ID)';
            $q = $db->prepare($sql);
            $q->bindValue(':keyword_ID', $keyword_ID);
            $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
            
            $q->execute();
            array_push($state, "insert search history");
    
        } catch (Exception $ex) {
            // Failed to insert the order into the database so we rollback any changes
            $db->rollback();
            _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
        }
    }

    // If the keyword has been searched for in the system, then update its number of searches
    try {
        $sql =  'UPDATE tKeyword SET nSearches = :count_searches 
                WHERE nKeywordID = :keyword_ID
                AND cKeyword = :keyword';
        $q = $db->prepare($sql);
        $q->bindValue(':keyword_ID', $keyword_ID);
        $q->bindValue(':keyword', $_POST['keyword']);
        $q->bindValue(':count_searches', $count_searches);

        $q->execute();

        if(!$q->rowCount()){
            $db->rollback();
            _res(400, ['info'=>'Failed to record keyword', 'error'=>__LINE__]);
        }

        array_push($state, "update keyword");

    } catch (Exception $ex) {
        // Failed to insert the order into the database so we rollback any changes
        $db->rollback();
        _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
    }

} // END // Keyword exists

// Keyword does not exist
if( ! $result ){
    // Begin the process of committing data to the database - initializing the transaction to preserve integrity
    $db->beginTransaction();

    try {
        // Insert keyword 
        $sql = 'INSERT INTO tKeyword (cKeyword) VALUES (:keyword)';
        $q = $db->prepare($sql);
        $q->bindValue(':keyword', $_POST['keyword']);
        
        $q->execute();

        array_push($state, "insert keyword");

        // Update the keyword_ID to be the value of the last inserted row
        $keyword_ID = $db->lastInsertId();

    } catch (Exception $ex) {
        // Failed to insert the order into the database so we rollback any changes
        $db->rollback();
        _res(500, ['info'=>$ex, 'error'=>__LINE__]);
    }

    try {
        // Insert keyword 
        $sql = 'INSERT INTO tSearchHistory (nKeywordID, nUserID) VALUES (:keyword_ID, :user_ID)';
        $q = $db->prepare($sql);
        $q->bindValue(':keyword_ID', $keyword_ID);
        $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
        
        $q->execute();
        array_push($state, "insert search history");

    } catch (Exception $ex) {
        // Failed to insert the order into the database so we rollback any changes
        $db->rollback();
        _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
    }
}

// If everything went well
// Make the changes to the database permanent
$db->commit();

_res(200, [ 'info'=>'Keyword recorded succesfully', 
'data'=>[
    'aState' => $state,
    'aKeyword' => [
        'nKeywordID' => $keyword_ID, 
        'cKeyword' => $_POST['keyword'], 
        'nSearches' => $count_searches
    ],
    'aSearchHistory' => [
        'nKeywordID' => $keyword_ID, 
        'cKeyword' => $_POST['keyword'], 
        'nSearches' => $count_searches_search_history
    ]
]
]);