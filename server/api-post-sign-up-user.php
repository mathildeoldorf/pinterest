<?php
require_once('globals.php');
require_once('config-PHPmailer.php');

if ( ! isset($_POST['email']) || empty($_POST['email']) ){ _res(400, ['info'=>'E-mail is required', 'error'=>__LINE__]); }
if ( ! filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ){ _res(400, ['info'=>'The e-mail is not a valid', 'error'=>__LINE__]); }

if ( ! isset($_POST['password']) || empty($_POST['password'])){ _res(400, ['info'=>'Password is required', 'error'=>__LINE__]); }
if ( strlen($_POST['password'] ) < _PASSWORD_MIN_LENGTH ){ _res(400, ['info'=>'Password is too short', 'error'=>__LINE__]); }
if ( strlen($_POST['password'] ) > _PASSWORD_MAX_LENGTH ){ _res(400, ['info'=>'Password is too long', 'error'=>__LINE__]); }
if ( ! isset($_POST['repeat_password']) || empty($_POST['repeat_password']) ){ _res(400, ['info'=>'You must repeat your chosen password', 'error'=>__LINE__]); }

if ( $_POST['password'] !== $_POST['repeat_password'] ){ _res(400, ['info'=>'Your passwords does not match', 'error'=>__LINE__]); }

// DB connection
$db = _db();

// Query
try {
    $db->beginTransaction();

    // Check if the user is already signed up
    $sql = 'SELECT * FROM tUser WHERE cEmail = :email';
    $q = $db->prepare($sql);
    $q->bindValue(':email', $_POST['email']);
    $q->execute();
    $result = $q->fetch();

    if($result){
        _res(400, ['info'=>'An account with the given e-mail already exists', 'error'=>__LINE__]);
    }
} catch(Exception $ex){
    $db->rollBack();
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}

try {
    // Insert user 
    $_POST['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $username = strtok($_POST['email'], '@');
    // GENERATE UNIQUE ACTIVATION CODE
    $key = md5( rand(0,1000) );

    $sql = 'INSERT INTO tUser (cEmail, cPassword, cUsername, cKey) VALUES (:email, :password, :username, :key)';
    $q = $db->prepare($sql);
    $q->bindValue(':email', $_POST['email']);
    $q->bindValue(':password', $_POST['password']);
    $q->bindValue(':username', $username);
    $q->bindValue(':key', $key);

    $q->execute();

    $user_ID = $db->lastInsertId();

} catch (Exception $ex) {
    $db->rollBack();
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}

try {

    // Recipient
    // $mail->addAddress($_POST['email'], $_POST['email']);     // Add a recipient
    $mail->addAddress('mathilde.kea.test@gmail.com', $_POST['email']);     // Add a recipient
    // Content
    $link = "https://fiostudio.com?action=activate&user_ID=$user_ID&key=$key";
    // $link = "localhost/client/public?action=activate&user_ID=$user_ID&key=$key";

    $mail->Subject = 'Welcome! Please verify your account';
    $mail->Body = "<h1>Welcome!</h1> <a href='$link'>Please click here to verify your account</a>";
    $mail->AltBody = "<h1>Welcome!</h1> <a href='$link'>Please click here to verify your account</a>";
    $success = $mail->send();

    if(!$success){
        $db->rollBack();
        _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
    }

    $last_inserted_id = $db->lastInsertId();
    $db->commit();

    _res(200, ['info'=>['header'=>'Welcome to Pinterest', 'subheader'=>'You have succesfully signed up', 'description'=>'We have sent you an email. Just follow the link to verify your account.'], 'data'=>$last_inserted_id]);
} catch (Exception $ex) {
    var_dump($ex);
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}
