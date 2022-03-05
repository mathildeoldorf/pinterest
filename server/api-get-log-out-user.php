<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();

session_start();
$_SESSION = array(); // destroy all $_SESSION data
setcookie("PHPSESSID", "", time() - 3600, "/");
session_destroy();

_res(200, ['info'=> 'You have been logged out succesfully', 'data'=>false]);