<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();

// Authenticate CSRF token to proceed
_csrf_auth();

if ( ! isset($_POST['pin_ID']) || empty($_POST['pin_ID']) ){ _res(400, ['info'=>'Pin ID is required', 'error'=>__LINE__]); }
if ( ! filter_var($_POST['pin_ID'], FILTER_VALIDATE_INT) ){ _res(400, ['info'=>'Invalid format', 'error'=>__LINE__]); }

// DB connection
$db = _db();

try {
    $sql = 'SELECT * FROM tLiked WHERE tLiked.nUserID = :user_ID AND tLiked.nPinID = :pin_ID';
    $q = $db->prepare($sql);
    $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    $q->bindValue(':pin_ID', $_POST['pin_ID']);
    
    $q->execute();
    
    $result = $q->fetch();

} catch (Exception $ex) {
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}

// Like exists
if($result){
    // Like is deactivated
    if( ! $result['bActive']){
        try {
            $sql = 'UPDATE tLiked SET bActive = 1 WHERE tLiked.nUserID = :user_ID AND tLiked.nPinID = :pin_ID';
            $q = $db->prepare($sql);
            $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
            $q->bindValue(':pin_ID', $_POST['pin_ID']);

            $q->execute();

            if(!$q->rowCount()){
                _res(400, ['info'=>'Failed to like', 'error'=>__LINE__]);
            }

            _res(200, ['info'=>'Pin liked succesfully', 'data'=>['user_ID' => $_SESSION['user']['nUserID'], 'pin_ID' => $_POST['pin_ID']]]);
        } catch (Exception $ex) {
            _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
        }
    }

    _res(400, ['info'=>'The pin is already liked', 'error'=>__LINE__]);
} 

// Like does not exist
try {
    // Insert like 
    $sql = 'INSERT INTO tLiked (nUserID, nPinID) VALUES (:user_ID, :pin_ID)';
    $q = $db->prepare($sql);
    $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    $q->bindValue(':pin_ID', $_POST['pin_ID']);
    
    $q->execute();

    _res(200, ['info'=>'Pin liked succesfully', 'data'=>['user_ID' => $_SESSION['user']['nUserID'], 'pin_ID' => $_POST['pin_ID']]]);
} catch (Exception $ex) {
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}