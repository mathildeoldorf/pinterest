<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();

// Authenticate CSRF token to proceed
_csrf_auth();

if ( ! isset($_POST['pin_ID']) || empty($_POST['pin_ID']) ){ _res(400, ['info'=>'Pin ID is required', 'error'=>__LINE__]); }
if ( ! filter_var($_POST['pin_ID'], FILTER_VALIDATE_INT) ){ _res(400, ['info'=>'Invalid format - Pin ID', 'error'=>__LINE__]); }

if ( ! isset($_POST['comment_ID']) || empty($_POST['comment_ID']) ){ _res(400, ['info'=>'Comment ID is required', 'error'=>__LINE__]); }
if ( ! filter_var($_POST['comment_ID'], FILTER_VALIDATE_INT) ){ _res(400, ['info'=>'Invalid format - Comment ID', 'error'=>__LINE__]); }

if ( ! isset($_POST['recipient_ID']) || empty($_POST['recipient_ID']) ){ _res(400, ['info'=>'Recipient ID is required', 'error'=>__LINE__]); }
if ( ! filter_var($_POST['recipient_ID'], FILTER_VALIDATE_INT) ){ _res(400, ['info'=>'Invalid format - Recipient ID', 'error'=>__LINE__]); }

if ( ! isset($_POST['comment']) || empty($_POST['comment']) ){ _res(400, ['info'=>'Comment is required', 'error'=>__LINE__]); }
if ( strlen($_POST['comment'] ) < _COMMENT_MIN_LENGTH ){ _res(400, ['info'=>'Comment is too short', 'error'=>__LINE__]); }
if ( strlen($_POST['comment'] ) > _COMMENT_MAX_LENGTH ){ _res(400, ['info'=>'Comment is too long', 'error'=>__LINE__]); }

// DB connection
$db = _db();

try {
    $sql =  'SELECT * FROM tComment 
            WHERE tComment.nSenderID = :user_ID AND tComment.nRecipientID = :recipient_ID AND tComment.nPinID = :pin_ID AND tComment.nCommentID = :comment_ID'; 

    $q = $db->prepare($sql);
    $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    $q->bindValue(':recipient_ID', $_POST['recipient_ID']);
    $q->bindValue(':comment_ID', $_POST['comment_ID']);
    $q->bindValue(':pin_ID', $_POST['pin_ID']);
    
    $q->execute();
    
    $result = $q->fetch();

} catch (Exception $ex) {
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}

// Comment does not exist
if( ! $result ){
    _res(404, ['info'=>'The comment does not exist', 'error'=>__LINE__]);
}
// Comment is not active
if( ! $result['bActive'] ){
    _res(404, ['info'=>'The comment is deleted', 'error'=>__LINE__]);
}

// Comment exists and is active
try {
    $sql =  'UPDATE tComment SET cComment = :comment 
            WHERE tComment.nSenderID = :user_ID AND tComment.nRecipientID = :recipient_ID AND tComment.nPinID = :pin_ID AND tComment.nCommentID = :comment_ID';

    $q = $db->prepare($sql);
    $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    $q->bindValue(':recipient_ID', $_POST['recipient_ID']);
    $q->bindValue(':comment_ID', $_POST['comment_ID']);
    $q->bindValue(':pin_ID', $_POST['pin_ID']);
    $q->bindValue(':comment', $_POST['comment']);

    $q->execute();

    if( ! $q->rowCount() ){
        _res(400, ['info'=>'Failed to update comment. Please try again.', 'error'=>__LINE__]);
    }

    _res(200, [ 'info'=>'Comment updated succesfully', 
                'data'=>[
                    'nCommentID'=> $_POST['comment_ID'],
                    'nSenderID' => $_SESSION['user']['nUserID'], 
                    'cSenderUsername' => $_SESSION['user']['cUsername'], 
                    'nRecipientID' => $_POST['recipient_ID'], 
                    'pin_ID' => $_POST['pin_ID'], 
                    'cComment' => $_POST['comment']
                ]
    ]);
} catch (Exception $ex) {
    _res(500, ['info'=>'System under maintainance', 'error'=>__LINE__]);
}