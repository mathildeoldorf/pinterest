<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();

if ( isset($_GET['user_ID'] ) ){ 
    if ( ! filter_var($_GET['user_ID'], FILTER_VALIDATE_INT) ){ _res(400, ['info'=>'Invalid format', 'error'=>__LINE__]); }
}

// DB connection
$db = _db();

// Query
try {
  $sql =  'SELECT followership.nFollowerID, 
                  followership.nFolloweeID,
                  followee.cUsername,
                  followee.cImage
          FROM tUser follower
          INNER JOIN tFollowership followership ON follower.nUserID = followership.nFollowerID
          INNER JOIN tUser followee ON followee.nUserID = followership.nFolloweeID
          WHERE followership.nFollowerID = :user_ID AND followership.bActive = 1
          AND follower.bActive = 1 AND followee.bActive = 1';

  $q = $db->prepare($sql);
 
  if ( isset($_GET['user_ID'] ) ){ 
    $q->bindValue(':user_ID', $_GET['user_ID']);
  }
  else{
    $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
  }

  $q->execute();
  $followees = $q->fetchAll();

} catch(Exception $ex){
  _res(500, ["info"=>$ex, "error"=>__LINE__]);
}

try {
  $sql =  'SELECT   followership.nFollowerID, 
                    followership.nFolloweeID,
                    follower.cUsername,
                    follower.cImage
          FROM tUser follower
          INNER JOIN tFollowership followership ON follower.nUserID = followership.nFollowerID
          INNER JOIN tUser followee ON followee.nUserID = followership.nFolloweeID
          WHERE followership.nFolloweeID = :user_ID AND followership.bActive = 1 
          AND follower.bActive = 1 AND followee.bActive = 1';

    $q = $db->prepare($sql);

    if ( isset($_GET['user_ID'] ) ){ 
        $q->bindValue(':user_ID', $_GET['user_ID']);
    }
    else{
        $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    }

    $q->execute();
    $followers = $q->fetchAll();
    
    _res(200, ['data'=>['aFollowing'=>$followees , 'aFollowers'=>$followers]]);
} catch(Exception $ex){
    _res(500, ["info"=>$ex, "error"=>__LINE__]);
}