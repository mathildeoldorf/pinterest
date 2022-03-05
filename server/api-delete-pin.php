<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();

// Authenticate CSRF token to proceed
_csrf_auth();

if ( ! isset($_POST['pin_ID']) || empty($_POST['pin_ID']) ){ _res(400, ['info'=>'Pin ID is required', 'error'=>__LINE__]); }
if ( ! filter_var($_POST['pin_ID'], FILTER_VALIDATE_INT) ){ _res(400, ['info'=>'Invalid format - Pin ID', 'error'=>__LINE__]); }

// DB connection
$db = _db();

// Check if the pin exists and is active
try {
    $sql =  'SELECT * FROM tPin 
            WHERE tPin.nPinID = :pin_ID
            AND tPin.nCreatorID = :user_ID'; 

    $q = $db->prepare($sql);
    $q->bindValue(':pin_ID', $_POST['pin_ID']);
    $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    
    $q->execute();
    
    $result = $q->fetch();

} catch (Exception $ex) {
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}

// Pin does not exist
if( ! $result){
    _res(401, ['info'=>'You are not authorized', 'error'=>__LINE__]);
}

// Pin is not active
if( ! $result['bActive']){
    _res(400, ['info'=>'The pin is deleted', 'error'=>__LINE__]);
}

// Pin exists and is active
try {
    $sql =  'UPDATE tPin SET bActive = 0 
            WHERE tPin.nPinID = :pin_ID
            AND tPin.nCreatorID = :user_ID';

    $q = $db->prepare($sql);
    $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    $q->bindValue(':pin_ID', $_POST['pin_ID']);

    $q->execute();

    if(!$q->rowCount()){
        _res(400, ['info'=>'Failed to delete pin', 'error'=>__LINE__]);
    }

    _res(200, [ 'info'=>['header'=> "Your pin $result[cTitle] was deleted succesfully", 'description'=>''], 
                'data'=>[
                    'nPinID'=> $_POST['pin_ID']
                ]
    ]);
} catch (Exception $ex) {
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}