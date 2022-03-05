<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();

// DB connection
$db = _db();

// Query
try {

    $sql =  'SELECT     DISTINCT 
                        tSearchHistory.nKeywordID, 
                        tKeyword.cKeyword, 
                        tSearchHistory.nSearches, 
                        tSearchHistory.dLastSearchedFor   
            FROM tKeyword
            INNER JOIN tSearchHistory ON tKeyword.nKeywordID = tSearchHistory.nKeywordID
            INNER JOIN tUser ON tSearchHistory.nUserID = tUser.nUserID
            WHERE tUser.nUserID = :user_ID
            ORDER BY tSearchHistory.nSearches DESC
            LIMIT 10';

    $q = $db->prepare($sql);
    $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);

    $q->execute();
    $result = $q->fetchAll();

    if( ! $result ){
        _res(400, ['info'=>'Failed to load your popular searches. Please try again.', 'error'=>__LINE__]);
    }

    _res(200, ['data' => $result]);
} catch(Exception $ex) {
    _res(500, ["info"=>"system under maintainance", "error"=>__LINE__]);
}