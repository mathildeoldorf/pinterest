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
    $sql = 'SELECT * FROM tUser WHERE tUser.nUserID = :user_ID AND tUser.bActive = 1';
    $q = $db->prepare($sql);
    $q->bindValue(':user_ID', $_POST['followee_ID']);
    
    $q->execute();
    
    $result = $q->fetch();

    if( ! $result ){
        _res(401, ['info'=>'You are not authorized. The user does not exist.', 'error'=>__LINE__, 'data'=>false]);
    }

} catch (Exception $ex) {
    var_dump($ex);
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}

try {
    $sql = 'SELECT * FROM tFollowership WHERE tFollowership.nFollowerID = :user_ID AND tFollowership.nFolloweeID = :followee_ID';
    $q = $db->prepare($sql);
    $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    $q->bindValue(':followee_ID', $_POST['followee_ID']);
    
    $q->execute();
    
    $result = $q->fetch();

} catch (Exception $ex) {
    _res(500, ['info'=>'System under maintainance', 'error'=>__LINE__]);
}

// Followership exists
if($result){
    // Followership is deactivated
    if( ! $result['bActive']){
        try {
            $sql = 'UPDATE tFollowership SET bActive = 1 WHERE tFollowership.nFollowerID = :user_ID AND tFollowership.nFolloweeID = :followee_ID';
            $q = $db->prepare($sql);
            $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
            $q->bindValue(':followee_ID', $_POST['followee_ID']);

            $q->execute();

            if(!$q->rowCount()){
                _res(400, ['info'=>"Failed to follow", 'error'=>__LINE__]);
            }

            _res(200, ['info'=>'You succesfully followed', 'data'=>['nFollowerID' => $_SESSION['user']['nUserID'], 'nFolloweeID' => $_POST['followee_ID']]]);
        } catch (Exception $ex) {
            _res(500, ['info'=>'System under maintainance', 'error'=>__LINE__]);
        }
        
    }

    _res(400, ['info'=>'You are already following this user', 'error'=>__LINE__]);
} 

// Followership does not exist
try {
    // Insert followership 
    $sql = 'INSERT INTO tFollowership (nFollowerID, nFolloweeID) VALUES (:user_ID, :followee_ID)';
    $q = $db->prepare($sql);
    $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    $q->bindValue(':followee_ID', $_POST['followee_ID']);
    
    $q->execute();

    _res(200, ['info'=>'You succesfully followed for the first time', 'data'=>['nFollowerID' => $_SESSION['user']['nUserID'], 'nFolloweeID' => $_POST['followee_ID']]]);
} catch (Exception $ex) {
    _res(500, ['info'=>'System under maintainance', 'error'=>__LINE__]);
}