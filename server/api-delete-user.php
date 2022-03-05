<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();

// DB connection
$db = _db();

// Query
try{
    $sql = 'UPDATE tUser SET bActive = 0 WHERE nUserID = :user_ID AND bActive = 1';
    
    $q = $db->prepare($sql);
    $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    $q->execute();

    if(!$q->rowCount()){
      _res(400, ['info'=>'User is already deleted', 'error'=>__LINE__]);
    }

    $user_ID = $_SESSION['user']['nUserID'];
    $_SESSION = array(); // destroy all $_SESSION data
    setcookie("PHPSESSID", "", time() - 3600, "/");
    session_destroy();
    
    _res(200, [
      'info'=>[
        'header'=> 'Thank you for using Pinterest', 
        'description'=>'Your account was deleted succesfully. We hope to see you again in the future.'
      ],
      'data'=>false
    ]);

} catch(Exception $ex){
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}
