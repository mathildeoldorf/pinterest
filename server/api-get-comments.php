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
            tComment.nCommentID,
            tComment.cComment,

            tComment.nSenderID,
            sender.cUsername as cSenderUsername,
            sender.cImage,

            tComment.nRecipientID,
            recipient.cUsername
            FROM tUser recipient
            INNER JOIN tComment ON tComment.nRecipientID = recipient.nUserID 
            INNER JOIN tUser sender ON tComment.nSenderID = sender.nUserID
            WHERE tComment.nPinID = :pin_ID AND tComment.bActive AND sender.bActive = 1 AND recipient.bActive = 1
            ORDER BY tComment.dCreated DESC';

    $q = $db->prepare($sql);
    $q->bindValue(':pin_ID', $_GET['pin_ID']);

    $q->execute();
    $result = $q->fetchAll();

    $data = [
        "aComments"=>$result,
        "nComments"=> count($result)
    ];

    _res(200, ['data' => $data]);
} catch(Exception $ex) {
    _res(500, ["info"=>"Failed to load comments. Please try again.", "error"=>__LINE__]);
}