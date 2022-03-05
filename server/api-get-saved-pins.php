<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();

// DB connection
$db = _db();

// If you are requesting another user's saved pins 
if ( isset($_GET['user_ID']) ) {
  if ( ! filter_var($_GET['user_ID'], FILTER_VALIDATE_INT) ){ _res(400, ['info'=>'Invalid format', 'error'=>__LINE__]); }
}

if ( ! isset($_GET['limit_start']) ){ _res(400, ['info'=>'No limit given', 'error'=>__LINE__, 'data'=> []]); }
if ( ! filter_var($_GET['limit_start'], FILTER_VALIDATE_INT) ){ if ( $_GET['limit_start'] !== '0' ){  _res(400, ['info'=>$_GET['limit_start'], 'error'=>__LINE__]); } }

if ( ! isset($_GET['limit_interval']) || empty($_GET['limit_interval']) ){ unset($_GET['limit_start']); }
if ( isset($_GET['limit_interval']) && ! empty($_GET['limit_interval']) && ! filter_var($_GET['limit_interval'], FILTER_VALIDATE_INT) ){ _res(400, ['info'=>'Invalid format: Interval', 'error'=>__LINE__]); }


// Query
try {

    $sql =    'SELECT  
              (SELECT COUNT(*) FROM tSaved INNER JOIN tPin ON tSaved.nPinID = tPin.nPinID WHERE tSaved.nUserID = :user_ID AND tSaved.bActive = 1 AND tPin.bActive = 1) AS limit_pins,
              tPin.nPinID, 
              tPin.cFileName, 
              tPin.cFileExtension, 
              tPin.cDescription, 
              tPin.cTitle, 
              tPin.cURL,
              tPin.nCreatorID,
              creator.cUsername, 
              creator.cImage
              FROM tPin
              INNER JOIN tSaved ON tPin.nPinID = tSaved.nPinID
              INNER JOIN tUser ON tSaved.nUserID = tUser.nUserID
              INNER JOIN tUser creator ON tPin.nCreatorID = creator.nUserID
              WHERE creator.bActive = 1 AND tUser.bActive = 1 AND tUser.nUserID = :user_ID AND tPin.bActive = 1 AND tSaved.bActive = 1
              ORDER BY tSaved.dCreated DESC';

    if ( isset($_GET['limit_start']) ) { $sql .= ' LIMIT :limit_start, :limit_interval'; }

    $q = $db->prepare($sql);
    
    if ( isset($_GET['user_ID']) ) {
      $q->bindValue(':user_ID', $_GET['user_ID']);
    } else {
      $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    }

    if ( isset($_GET['limit_start']) ) {
      $q->bindValue(':limit_start', $_GET['limit_start'], PDO::PARAM_INT);
      $q->bindValue(':limit_interval', $_GET['limit_interval'], PDO::PARAM_INT);
    }

    $q->execute();
    $result = $q->fetchAll();

    if( ! $result ){
        _res(200, ['info'=> isset($_GET['user_ID']) ? 'There aren’t any pins on the saved board yet' : 'There aren’t any pins on your saved board yet', 'data'=> []]);
    }

    _res(200, ['limit'=>$result[0]['limit_pins'], 'data' => $result]);
} catch(Exception $ex) {
  var_dump($ex);
  _res(500, ["info"=>"system under maintainance", "error"=>__LINE__]);
}