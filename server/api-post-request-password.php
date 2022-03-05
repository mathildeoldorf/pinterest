<?php
require_once('globals.php');
require_once('config-PHPmailer.php');

if ( ! isset($_POST['email']) || empty($_POST['email']) ){ _res(400, ['info'=>'E-mail is required', 'error'=>__LINE__]); }
if ( ! filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ){ _res(400, ['info'=>'The e-mail is not a valid', 'error'=>__LINE__]); }

// DB connection
$db = _db();

// Query
try {
    $db->beginTransaction();
    // GENERATE UNIQUE ACTIVATION CODE
    $key = md5( rand(0,1000) );

    $sql = 'UPDATE tUser SET cKey = :key WHERE cEmail = :email AND bActive = 1';
    $q = $db->prepare($sql);
    $q->bindValue(':email', $_POST['email']);
    $q->bindValue(':key', $key);
    $q->execute();

    if ( ! $q->rowCount() ){
        _res(400, ['info'=>'You are not authorized to request a new password', 'error'=>__LINE__]);
    }

} catch(Exception $ex){
    $db->rollBack();
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}

try {
    // Recipient
    // $mail->addAddress($_POST['email'], $_POST['email']);     // Add a recipient
    $mail->addAddress('mathilde.kea.test@gmail.com', $_POST['email']);     // Add a recipient
    // Content
    $link = "https://fiostudio.com?action=change-password&key=$key";
    // $link = "http://localhost/client/public?action=change-password&key=$key";

    $mail->Subject = "A new password has been requested.";
    $mail->Body    =    "<h1>Hi!</h1> 
                        <p>You have recently requested to change your password.</p>
                        <p>Please follow this link to change your password:</p>
                        <a href='$link'>Change password</a>";
    $mail->AltBody = "<h1>Hi!</h1>You have recently requested to change your password. Please follow this link to change your password: $link";
    $success = $mail->send();

    if(!$success){
        $db->rollBack();
        _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
    }

    $db->commit();

    _res(200, ['info'=>['header'=>'Welcome to Pinterest', 'subheader'=>'You have succesfully requested a new password', 'description'=>'We have sent you an email. Just follow the link to change your password.'], 'data'=>$db->lastInsertId()]);

} catch (Exception $ex) {
    $db->rollBack();
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}