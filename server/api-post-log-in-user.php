<?php
require_once('globals.php');

if ( ! isset($_POST['email']) || empty($_POST['email']) ){ _res(400, ['info'=>'E-mail is required', 'error'=>__LINE__]); }
if ( ! filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ){ _res(400, ['info'=>'The e-mail is not a valid', 'error'=>__LINE__]); }

if ( ! isset($_POST['password']) || empty($_POST['password']) ){ _res(400, ['info'=>'Password is required', 'error'=>__LINE__]); }
if ( strlen($_POST['password'] ) < _PASSWORD_MIN_LENGTH ){ _res(400, ['info'=>'Password is too short', 'error'=>__LINE__]); }
if ( strlen($_POST['password'] ) > _PASSWORD_MAX_LENGTH ){ _res(400, ['info'=>'Password is too long', 'error'=>__LINE__]); }

// DB connection
$db = _db();

// Query
try {
    // Check if the user is registered
    $sql = 'SELECT * FROM tUser WHERE cEmail = :email';
    $q = $db->prepare($sql);
    // $q = $db->prepare("CALL _login($_POST[email])");
    $q->bindValue(':email', $_POST['email']);
    $q->execute();
    $result = $q->fetch();

    if(!$result){
        _res(400, ['info'=>'The given e-mail is not registered. Please sign up', 'error'=>__LINE__, 'data'=>false]);
    }

    // Check if the user is deactivated
    if(!$result['bActive']){
        _res(400, ['info'=>'The given e-mail is not registered. Please sign up', 'error'=>__LINE__, 'data'=>false]);
    }

    if(!password_verify($_POST['password'], $result['cPassword'])){
        _res(400, ['info'=>'The given credentials are incorrect. Please try again', 'error'=>__LINE__, 'data'=>false]);
    }
    
    // SESSION
    session_start(); 
    unset($result['cPassword']);
    $csrf_token = bin2hex(random_bytes(16));
    $result['csrf_token'] = $csrf_token;
    $_SESSION['user'] = $result;

    _res(200, ['info'=> 'You have logged in succesfully', 'data'=>$_SESSION['user']]);

} catch(Exception $ex){
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}