<?php
require_once('globals.php');

if ( ! isset($_POST['user_ID']) || empty($_POST['user_ID']) ){ _res(400, ['info'=>'You are not authorized', 'error'=>__LINE__]); }
if ( ! filter_var($_POST['user_ID'], FILTER_VALIDATE_INT) ){ _res(400, ['info'=>'You are not authorized', 'error'=>__LINE__]); }

if ( ! isset($_POST['key']) || empty($_POST['key']) ){ _res(400, ['info'=>'You are not authorized', 'error'=>__LINE__]); }
if ( strlen($_POST['key']) !== _KEY_LENGTH ){ _res(400, ['info'=>'Key is invalid', 'error'=>__LINE__]); }

// DB connection
$db = _db();

// Query
try {

    $sql = 'UPDATE tUser SET bActive = 1, cKey = NULL WHERE nUserID = :user_ID AND cKey = :key AND bActive = 0';
    $q = $db->prepare($sql);
    $q->bindValue(':user_ID', $_POST['user_ID']);
    $q->bindValue(':key', $_POST['key']);
    $q->execute();
    
    if ( ! $q->rowCount() ){
        _res(400, ['info'=>'Failed to activate account', 'error'=>__LINE__]);
    }

    _res(200, ['info'=> ['header'=>'Welcome to Pinterest', 'subheader'=>'You have succesfully activated your account', 'description'=>'The world of inspiration that constitutes Pinterest awaits you. Please log in to proceed.'], 'data'=>1]);


} catch(Exception $ex){
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}