<?php
// ############################## COOKIE SETTINGS TO PROTECT AGAINST XSS AND CSRF
ini_set( 'session.cookie_httponly', 1 ); // Prevents the cookie from being read in JS (XSS)
ini_set( 'session.cookie_secure', 1 ); // Cookies are only sent when the connection is HTTPS 
ini_set( 'session.cookie_samesite', 'Strict' ); // Cookies are only sent in requests from this website (CSRF)

// ############################## CORS CONTROL - same http protocol - same port number - same domain name
header('Access-Control-Allow-Origin: 5000');

// ############################## DATA RESTRICTIONS
define('_NAME_MIN_LENGTH', 2);
define('_NAME_MAX_LENGTH', 50);

define('_PASSWORD_MIN_LENGTH', 8);
define('_PASSWORD_MAX_LENGTH', 20);

define('_COMMENT_MIN_LENGTH', 2);
define('_COMMENT_MAX_LENGTH', 255);

define('_TITLE_MIN_LENGTH', 2);
define('_TITLE_MAX_LENGTH', 100);

define('_DESCRIPTION_MIN_LENGTH', 2);
define('_DESCRIPTION_MAX_LENGTH', 255);

define('_URL_MIN_LENGTH', 8);
define('_URL_MAX_LENGTH', 255);

define('_TERM_MIN_LENGTH', 3);
define('_TERM_MAX_LENGTH', 50);

define('_KEY_LENGTH', 32);

// Sizes in bytes // (Byte to KB = Bytes/1024) | (Byte to MB = (Bytes/1024)/1024)
define('_KB', 1024);
define('_MB', 1048576);
define('_GB', 1073741824);
define('_TB', 1099511627776);


// ##############################
// Authentication of csrf token function
function _csrf_auth(){
  if( ! isset($_POST['csrf_token']) ) 
  _res(401, ["info"=>'You are not authorized', 'error'=>__LINE__]);

  if( $_POST['csrf_token'] !== $_SESSION['user']['csrf_token'] ) 
  _res(401, ["info"=>'You are not authorized', 'error'=>__LINE__]);
}

// ##############################
// Authentication function
function _auth(){
  try {
    session_start(); 
    if( ! isset( $_SESSION['user'] ) ){
      $_SESSION = array(); // destroy all $_SESSION data
      setcookie("PHPSESSID", "", time() - 3600, "/");
      session_destroy();
      _res(401, ['info'=>'You are not authorized', 'error'=>__LINE__, 'data'=>false]);
    }
  } catch (Exception $ex) {
    _res(500, ["info"=>'Authentication failed', 'error'=>__LINE__]);
  }
}

// ##############################
// Response function
function _res($status=200, $message=[]){
  try {
    http_response_code($status); 
    header('Content-Type: application/json'); 
    echo json_encode($message); 
    exit();
  } catch (Exception $ex) {
    _res(500, ["info"=>"Res failed"]);
  }
}

// ##############################
// PDO function
function _db(){
  try {
    $config = include('config.php');
    return new PDO( $config['connection'], $config['username'], $config['password'], $config['options'] ); 
      
  } catch(Exception $ex) {
      _res(500, ['info'=>'uppps... cannot connect to db']);
  }
}