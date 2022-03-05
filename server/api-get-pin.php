<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();


if ( ! isset($_GET['pin_ID']) || empty($_GET['pin_ID']) ){ _res(400, ['info'=>'Pin ID is required', 'error'=>__LINE__]); }
if ( ! filter_var($_GET['pin_ID'], FILTER_VALIDATE_INT) ){ _res(400, ['info'=>'Invalid format', 'error'=>__LINE__]); }

// DB connection
$db = _db();

// Query
try {
  $sql =    'SELECT  
            tPin.nPinID, 
            tPin.cFileName, 
            tPin.cFileExtension, 
            tPin.cDescription, 
            tPin.cTitle, 
            tPin.cURL,
            tPin.nCreatorID,
            tUser.cUsername,
            tUser.cImage
            FROM tPin
            INNER JOIN tUser ON tPin.nCreatorID = tUser.nUserID 
            WHERE tPin.nPinID = :pin_ID AND tUser.bActive = 1 AND tPin.bActive = 1';

    $q = $db->prepare($sql);
    $q->bindValue(':pin_ID', $_GET['pin_ID']);

    $q->execute();
    $result = $q->fetch();

    if( ! $result ){
        _res(400, ['info'=>'Failed to load the selected pin. Please try again.', 'data'=>[]]);
    }

    _res(200, ['data' => $result]);
} catch(Exception $ex) {
    _res(500, ["info"=>"system under maintainance", "error"=>__LINE__]);
}