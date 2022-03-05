<?php
require_once('globals.php');

if( ! isset($_POST['key']) || empty($_POST['key']) ){
    // Authenticate user to proceed
    _auth();
    // Authenticate CSRF token to proceed
    _csrf_auth();
}

// Check data
if( ! isset($_POST['password']) || empty($_POST['password']) ){ _res(400, ['info'=>'Please submit your new password, before proceeding', 'error'=>__LINE__]); }
if ( strlen($_POST['password'] ) < _PASSWORD_MIN_LENGTH ){ _res(400, ['info'=>'Your new password is too short. Minimum 8 characters', 'error'=>__LINE__]); }
if ( strlen($_POST['password'] ) > _PASSWORD_MAX_LENGTH ){ _res(400, ['info'=>'Your new password is too long. Maximum 20 characters.', 'error'=>__LINE__]); }

//// Checks for a request without a key, meaning the user is logged in
if( ! isset($_POST['key']) ){
    if( ! isset($_POST['old_password']) || empty($_POST['old_password']) ){ _res(400, ['info'=>'Please submit your old password, before proceeding', 'error'=>__LINE__]); }
    if( $_POST['password'] === $_POST['old_password'] ){ _res(400, ['info'=>'The old and new password cannot be identical.', 'error'=>__LINE__]); }
}

if ( isset($_POST['key']) ){ 
    if( ! isset($_POST['repeat_password']) || empty($_POST['repeat_password']) ){ _res(400, ['info'=>'Please repeat your new password, before proceeding', 'error'=>__LINE__]); }
    if ( empty($_POST['key']) ){ _res(400, ['info'=>'Key is invalid', 'error'=>__LINE__]); }
    if ( strlen($_POST['key']) !== _KEY_LENGTH ){ _res(400, ['info'=>'Key is invalid', 'error'=>__LINE__]); }
}

// DB connection
$db = _db();

try {
    $sql;

    // Check the old password
    if( ! isset($_POST['key']) ){ 
        $sql = 'SELECT nUserID, bActive, cPassword FROM tUser WHERE nUserID = :user_ID';
    } else {
        $sql = 'SELECT nUserID, bActive FROM tUser WHERE cKey = :key';
    }
    
    $q = $db->prepare($sql);
    
    if( ! isset($_POST['key']) ) {
        $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    } else {
        $q->bindValue(':key', $_POST['key']);
    }
    
    $q->execute();
    $result = $q->fetch();
    
    if( ! $result ){
        _res(400, ['info'=>'You are not authorized', 'error'=>__LINE__]);
    }

    // Check if the user is deactivated
    if( ! $result['bActive'] ){
        _res(400, ['info'=>'You are not authorized', 'error'=>__LINE__]);
    }

    if( ! isset($_POST['key']) && ! password_verify($_POST['old_password'], $result['cPassword'])){
        _res(400, ['info'=>'The given credentials are incorrect. Please try again', 'error'=>__LINE__]);
    }

} catch(Exception $ex){
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}

try {
    $_POST['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);

    if( ! isset($_POST['key']) ){ 
        $sql = 'UPDATE tUser SET cPassword = :password WHERE nUserID = :user_ID';
    } else {
        $sql = 'UPDATE tUser SET cPassword = :password, cKEY = NULL WHERE cKey = :key AND nUserID = :user_ID';
    }

    $q = $db->prepare($sql);
    $q->bindValue(':password', $_POST['password']);
    
    if( ! isset($_POST['key']) ){
        $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    } else {
        $q->bindValue(':user_ID', $result['nUserID']);
        $q->bindValue(':key', $_POST['key']);
    }

    $q->execute();

    if ( ! $q->rowCount() ){
        _res(400, ['info'=>'Failed to update your information. Please try again.', 'error'=>__LINE__]);
    }

    if( ! isset($_POST['key']) ) _res(200, ['info'=>'Your password was updated succesfully.', 'data'=> 1]);
    if( isset($_POST['key']) ) _res(200, ['info'=>['header'=>'Your password is now updated', 'description'=>'You can now log in to your account and gather inspiration in the world of Pinterest.'], 'data'=> 1]);
} catch (Exception $ex) {
    var_dump($ex);
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}