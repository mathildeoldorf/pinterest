<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();

// DB connection
$db = _db();

// Query
try {

    $sql =  'SELECT *   
            FROM tKeyword
            ORDER BY tKeyword.nSearches DESC
            LIMIT 10';

    $q = $db->prepare($sql);

    $q->execute();
    $result = $q->fetchAll();

    if( ! $result ){
        _res(400, ['info'=>'Failed to load the popular searches. Please try again.', 'error'=>__LINE__]);
    }

    _res(200, ['data' => $result]);
} catch(Exception $ex) {
    _res(500, ["info"=>"system under maintainance", "error"=>__LINE__]);
}