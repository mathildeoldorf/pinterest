<?php
require_once('globals.php');

// DB connection
$db = _db();

if ( ! isset($_POST['user_ID']) || empty($_POST['user_ID']) ){ _res(400, ['info'=>'User ID is required', 'error'=>__LINE__]); }
if ( ! filter_var($_POST['user_ID'], FILTER_VALIDATE_INT) ){ _res(400, ['info'=>'Invalid format - User ID', 'error'=>__LINE__]); }

// Query
try {
    $sql = 'UPDATE tUser SET bActive = 1 WHERE nUserID = :user_ID AND bActive = 0';
    
    $q = $db->prepare($sql);
    $q->bindValue(':user_ID', $_POST['user_ID']);
    $q->execute();

    if(!$q->rowCount()){
        _res(200, ['info'=>'User is already active', 'error'=>__LINE__]);
    }

    _res(200, ['info'=>'User reactivated succesfully', 'data'=>$_POST['user_ID']]);

} catch(Exception $ex){
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}