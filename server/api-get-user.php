<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();

if ( isset($_GET['user_ID']) ){ 
  if ( ! filter_var($_GET['user_ID'], FILTER_VALIDATE_INT) ){ _res(400, ['info'=>'Invalid format', 'error'=>__LINE__]); }
}

// DB connection
$db = _db();

// Query
try {
  $sql = 'SELECT nUserID, cFirstName, cLastName, cEmail, cUsername, cImage, cDescription, cURL, bActive FROM tUser WHERE nUserID = :user_ID AND bActive = 1';
  $q = $db->prepare($sql);

  if ( isset($_GET['user_ID'] ) ){ 
    $q->bindValue(':user_ID', $_GET['user_ID']);
  }
  else{
    $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
  }

  $q->execute();
  $result = $q->fetch();

  if( ! $result ){
    _res(400, ['info'=>'Failed to load the requested user. Please try again.', 'error'=>__LINE__, 'data'=> []]);
  }
  _res(200, ['data'=>$result]);

} catch(Exception $ex){
  _res(500, ["info"=>"system under maintainance", "error"=>__LINE__]);
}
