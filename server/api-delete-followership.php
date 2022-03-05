<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();

// Authenticate CSRF token to proceed
_csrf_auth();

if ( ! isset($_POST['followee_ID']) || empty($_POST['followee_ID']) ){ _res(400, ['info'=>'Followee ID is required', 'error'=>__LINE__]); }
if ( ! filter_var($_POST['followee_ID'], FILTER_VALIDATE_INT) ){ _res(400, ['info'=>'Invalid format', 'error'=>__LINE__]); }

// DB connection
$db = _db();

try {
    // Remove / Deactivate like
    $sql = 'UPDATE tFollowership SET bActive = 0 WHERE nFollowerID = :user_ID AND nFolloweeID = :followee_ID';
    $q = $db->prepare($sql);
    $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    $q->bindValue(':followee_ID', $_POST['followee_ID']);
    
    $q->execute();

    if(!$q->rowCount()){
        _res(400, ['info'=>'Failed to unfollow', 'error'=>__LINE__]);
    }

    _res(200, ['info'=>'You have unfollowed succesfully', 'data'=>['user_ID' => $_SESSION['user']['nUserID'], 'followee_ID' => $_POST['followee_ID']]]);
} catch (Exception $ex) {
    var_dump($ex);
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}