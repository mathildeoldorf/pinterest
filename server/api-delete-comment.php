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

// DB connection
$db = _db();

// Check if the comment exists and is active
try {
    $sql =  'SELECT * FROM tComment WHERE (nRecipientID = :user_ID OR nSenderID = :user_ID) AND nPinID = :pin_ID AND nCommentID = :comment_ID'; 

    $q = $db->prepare($sql);
    $q->bindValue(':comment_ID', $_POST['comment_ID']);
    $q->bindValue(':pin_ID', $_POST['pin_ID']);
    $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    
    $q->execute();
    $result = $q->fetch();

} catch (Exception $ex) {
    _res(500, ['info'=>'System under maintainance', 'error'=>__LINE__]);
}

// Comment does not exist
if( ! $result ){
    _res(401, ['info'=>'You are not authorized to delete this comment', 'error'=>__LINE__]);
}

// Comment is not active
if( ! $result['bActive'] ){
    _res(404, ['info'=>'The comment is already deleted', 'error'=>__LINE__]);
}

if( $_SESSION['user']['nUserID'] === $result['nRecipientID'] ){
    $deleter = [ 'nRecipientID' => $_SESSION['user']['nUserID'] ];
    $user_to_notify = [ 'nSenderID' => $result['nSenderID'] ];

    $sql =  'UPDATE tComment SET bActive = 0 
            WHERE nRecipientID = :user_ID AND nPinID = :pin_ID AND nCommentID = :comment_ID';
}

if( $_SESSION['user']['nUserID'] === $result['nSenderID'] ){
    $deleter = [ 'nSenderID' => $_SESSION['user']['nUserID'] ];
    $user_to_notify = [ 'nRecipientID' => $result['nRecipientID'] ];

    $sql =  'UPDATE tComment SET bActive = 0 
            WHERE nSenderID = :user_ID AND nPinID = :pin_ID AND nCommentID = :comment_ID';
}

// Comment exists and is active
try {

    $q = $db->prepare($sql);
    $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    $q->bindValue(':comment_ID', $_POST['comment_ID']);
    $q->bindValue(':pin_ID', $_POST['pin_ID']);

    $q->execute();

    if(!$q->rowCount()){
        _res(400, ['info'=>'Failed to delete comment. Please try again.', 'error'=>__LINE__]);
    }

    _res(200, [ 'info'=>'Comment deleted succesfully', 
                'data'=>[
                    'nCommentID'=> $_POST['comment_ID'],
                    'deleter' => $deleter,
                    'user_to_notify' => $user_to_notify
                ]
            ]
    );
} catch (Exception $ex) {
    _res(500, ['info'=>'Failed to delete comment. Please try again.', 'error'=>__LINE__]);
}