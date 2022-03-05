<?php
require_once('globals.php');
try {
    session_start(); 
    if( ! isset( $_SESSION['user'] ) ){
      $_SESSION = array(); // destroy all $_SESSION data
      setcookie("PHPSESSID", "", time() - 3600, "/");
      session_destroy();
      _res(401, ['info'=>'You are not authorized', 'error'=>__LINE__, 'data'=>false]);
    }
    _res(200, ['info'=>'You are authenticated', 'data'=>$_SESSION['user']]);
} catch (Exception $ex) {
    _res(500, ["info"=>'Authentication failed', 'error'=>__LINE__]);
}