<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();

// DB connection
$db = _db();

// If you are requesting another user's pins 
if ( isset($_GET['user_ID']) ) {
  if ( ! filter_var($_GET['user_ID'], FILTER_VALIDATE_INT) ){ _res(400, ['info'=>'Invalid format', 'error'=>__LINE__]); }
}

// Query
try {

    $sql = 'SELECT  tPin.nPinID, 
                    tPin.cFileName, 
                    tPin.cFileExtension,
                    (SELECT COUNT(*) FROM tPin INNER JOIN tUser ON tPin.nCreatorID = tUser.nUserID WHERE tUser.nUserID = :user_ID AND tPin.bActive = 1) as nPins
            FROM tPin
            INNER JOIN tUser creator ON tPin.nCreatorID = creator.nUserID
            WHERE creator.nUserID = :user_ID
            AND creator.bActive = 1 AND tPin.bActive = 1
            GROUP BY tPin.nPinID
            ORDER BY tPin.dCreated DESC
            -- LIMIT 3
            ';

    $q = $db->prepare($sql);

    if ( isset($_GET['user_ID']) ) {
        $q->bindValue(':user_ID', $_GET['user_ID']);
    } else {
        $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    }

    $q->execute();
    $result = $q->fetchAll();
    $owned = [];
    $owned['aPins'] = $result ? $result : [];
    $owned['nPins'] = $result ? $owned['aPins'][0]['nPins'] : 0;

    $result = false;

    
    $sql =    'SELECT   tPin.nPinID, 
                        tPin.cFileName, 
                        tPin.cFileExtension,
                        (SELECT COUNT(*) FROM tUser INNER JOIN tLiked ON tUser.nUserID = tLiked.nUserID WHERE tLiked.nUserID = :user_ID AND tPin.bActive = 1 AND tLiked.bActive) as nPins
                FROM tPin
                INNER JOIN tLiked ON tPin.nPinID = tLiked.nPinID
                INNER JOIN tUser ON tLiked.nUserID = tUser.nUserID
                INNER JOIN tUser creator ON tPin.nCreatorID = creator.nUserID
                WHERE  creator.bActive = 1 AND tUser.bActive = 1 AND tUser.nUserID = :user_ID AND tPin.bActive = 1 AND tLiked.bActive = 1
                ORDER BY tLiked.dCreated DESC
                -- LIMIT 3
                ';

    $q = $db->prepare($sql);

    if ( isset($_GET['user_ID']) ) {
        $q->bindValue(':user_ID', $_GET['user_ID']);
    } else {
        $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    }

    $q->execute();
    $result = $q->fetchAll();
    $liked = [];
    $liked['aPins'] = $result ? $result : [];
    $liked['nPins'] = $result ? $liked['aPins'][0]['nPins'] : 0;

    $result = false;

    $sql =    'SELECT tPin.nPinID, 
                    tPin.cFileName, 
                    tPin.cFileExtension,
                    (SELECT COUNT(*) FROM tUser INNER JOIN tSaved ON tUser.nUserID = tSaved.nUserID WHERE tSaved.nUserID = :user_ID AND tPin.bActive = 1 AND tSaved.bActive) as nPins
            FROM tPin
            INNER JOIN tSaved ON tPin.nPinID = tSaved.nPinID
            INNER JOIN tUser ON tSaved.nUserID = tUser.nUserID
            INNER JOIN tUser creator ON tPin.nCreatorID = creator.nUserID
            WHERE  creator.bActive = 1 AND tUser.bActive = 1 AND tUser.nUserID = :user_ID AND tPin.bActive = 1 AND tSaved.bActive = 1
            ORDER BY tSaved.dCreated DESC
            -- LIMIT 3
            ';

    $q = $db->prepare($sql);

    if ( isset($_GET['user_ID']) ) {
        $q->bindValue(':user_ID', $_GET['user_ID']);
    } else {
        $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    }

    $q->execute();
    $result = $q->fetchAll();
    $saved = [];
    $saved['aPins'] = $result ? $result : [];
    $saved['nPins'] = $result ? $saved['aPins'][0]['nPins'] : 0;

    _res(200, ['data' => ['owned' => $owned, 'saved'=> $saved, 'liked'=> $liked]]);
} catch(Exception $ex) {
    _res(500, ["info"=>"system under maintainance", "error"=>__LINE__]);
}