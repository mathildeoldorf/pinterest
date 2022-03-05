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
    $sql =  'SELECT  
            tLiked.nPinID,
            tLiked.nUserID,
            tUser.cUsername,
            tLiked.bActive
            FROM tUser
            INNER JOIN tLiked ON tUser.nUserID = tLiked.nUserID 
            WHERE tLiked.nPinID = :pin_ID AND tLiked.bActive AND tUser.bActive = 1';

    $q = $db->prepare($sql);
    $q->bindValue(':pin_ID', $_GET['pin_ID']);

    $q->execute();
    $result = $q->fetchAll();

    $data = [
        "aLikes"=>$result,
        "nLikes"=> count($result)
    ];

    _res(200, ['data' => $data]);
} catch(Exception $ex) {
    var_dump($ex);
    _res(500, ["info"=>"system under maintainance", "error"=>__LINE__]);
}