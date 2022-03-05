<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();

// DB connection
$db = _db();

// Query
try {
  $sql = 'SELECT nUserID, cFirstName, cLastName, cEmail, cUsername, cImage, cDescription, cURL, bActive FROM tUser';
  $q = $db->prepare($sql);
  $q->execute();
  $result = $q->fetchAll();

  if( ! $result ){
    _res(400, ['info'=>'Failed to load all users. Please try again.', 'error'=>__LINE__, 'data'=> []]);
  }
  
  _res(200, $result);

} catch(Exception $ex){
  _res(500, ["info"=>"system under maintainance", "error"=>__LINE__]);
}