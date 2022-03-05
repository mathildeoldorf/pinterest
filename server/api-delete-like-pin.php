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
    // Remove / Deactivate like
    $sql = 'UPDATE tLiked SET bActive = 0 WHERE nUserID = :user_ID AND nPinID = :pin_ID';
    $q = $db->prepare($sql);
    $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    $q->bindValue(':pin_ID', $_POST['pin_ID']);
    
    $q->execute();

    if(!$q->rowCount()){
        _res(400, ['info'=>'Failed to unlike', 'error'=>__LINE__]);
    }

    _res(200, ['info'=>'Pin unliked succesfully', 'data'=>['user_ID' => $_SESSION['user']['nUserID'], 'pin_ID' => $_POST['pin_ID']]]);
} catch (Exception $ex) {
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}