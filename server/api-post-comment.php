<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();

// Authenticate CSRF token to proceed
_csrf_auth();

if ( ! isset($_POST['pin_ID']) || empty($_POST['pin_ID']) ){ _res(400, ['info'=>'Pin ID is required', 'error'=>__LINE__]); }
if ( ! filter_var($_POST['pin_ID'], FILTER_VALIDATE_INT) ){ _res(400, ['info'=>'Invalid format - Pin ID', 'error'=>__LINE__]); }

if ( ! isset($_POST['recipient_ID']) || empty($_POST['recipient_ID']) ){ _res(400, ['info'=>'Recipient ID is required', 'error'=>__LINE__]); }
if ( ! filter_var($_POST['recipient_ID'], FILTER_VALIDATE_INT) ){ _res(400, ['info'=>'Invalid format - Recipient ID', 'error'=>__LINE__]); }

if ( ! isset($_POST['comment']) || empty($_POST['comment']) ){ _res(400, ['info'=>'Comment is required', 'error'=>__LINE__]); }
if ( strlen($_POST['comment'] ) < _COMMENT_MIN_LENGTH ){ _res(400, ['info'=>'Comment is too short', 'error'=>__LINE__]); }
if ( strlen($_POST['comment'] ) > _COMMENT_MAX_LENGTH ){ _res(400, ['info'=>'Comment is too long', 'error'=>__LINE__]); }

// DB connection
$db = _db();

// Check if pin is active  
try {
    $sql = 'SELECT nCreatorID FROM tPin WHERE tPin.nPinID = :pin_ID AND tPin.bActive = 1';
    $q = $db->prepare($sql);
    $q->bindValue(':pin_ID', $_POST['pin_ID']);
    
    $q->execute();
    
    $result = $q->fetch();

} catch (Exception $ex) {
    _res(500, ['info'=>'System under maintainance', 'error'=>__LINE__]);
}

if( ! $result) _res(404, ['info'=>'Failed to post the comment. The pin does not exist.', 'error'=>__LINE__]);

// Check if the pin belongs to the recipient (only needed when the creator of the pin is always the recipient)
if( $result['nCreatorID'] !== $_POST['recipient_ID'] ) _res(403, ['info'=>'You can currently only send a comment to the creator of this pin', 'error'=>__LINE__]);

try {
    // Insert comment 
    $sql = 'INSERT INTO tComment (nSenderID, nRecipientID, nPinID, cComment) VALUES (:sender_ID, :recipient_ID, :pin_ID, :comment)';
    $q = $db->prepare($sql);
    $q->bindValue(':sender_ID', $_SESSION['user']['nUserID']);
    $q->bindValue(':recipient_ID', $_POST['recipient_ID']);
    $q->bindValue(':pin_ID', $_POST['pin_ID']);
    $q->bindValue(':comment', $_POST['comment']);
    
    $q->execute();

    _res(200,[  'info'=>'Pin commented succesfully', 
                'data'=>[
                    'nCommentID'        => $db->lastInsertId(),
                    'nSenderID'         => $_SESSION['user']['nUserID'], 
                    'cSenderUsername'   => $_SESSION['user']['cUsername'],
                    'nRecipientID'      => $_POST['recipient_ID'],
                    'cComment'          => $_POST['comment'],
                    'cImage'            => $_SESSION['user']['cImage'],
                ]
    ]);

} catch (Exception $ex) {
    _res(500, ['info'=>'Failed to post the comment. Please try again.', 'error'=>__LINE__]);
}