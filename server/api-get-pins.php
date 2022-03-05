<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();

// DB connection
$db = _db();

if ( ! isset($_GET['limit_start']) ){ _res(400, ['info'=>'No limit given', 'error'=>__LINE__, 'data'=> []]); }
if ( ! filter_var($_GET['limit_start'], FILTER_VALIDATE_INT) ){ if ( $_GET['limit_start'] !== '0' ){  _res(400, ['info'=>'Invalid format: Limit start', 'error'=>__LINE__]); } }

if ( ! isset($_GET['limit_interval']) || empty($_GET['limit_interval']) ){ _res(400, ['info'=>'No interval given', 'error'=>__LINE__, 'data'=> []]); }
if ( ! filter_var($_GET['limit_interval'], FILTER_VALIDATE_INT) ){ _res(400, ['info'=>'Invalid format: Interval', 'error'=>__LINE__]); }

// Query for keywords
try {
  $keywords = "";
  $limit_keywords = 10;

  $sql =  'SELECT tKeyword.cKeyword FROM tSearchHistory 
          INNER JOIN tKeyword ON tSearchHistory.nKeywordID = tKeyword.nKeywordID 
          WHERE tSearchHistory.nUserID = :user_ID 
          ORDER BY tSearchHistory.nSearches DESC 
          LIMIT :limit_keywords';

  $q = $db->prepare($sql);

  $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
  $q->bindValue(':limit_keywords', $limit_keywords, PDO::PARAM_INT);

  $q->execute();

  $result = $q->fetchAll();

  $count = count($result);
  $limit = $limit_keywords-$count;

  if( $result ){
    foreach ($result as $param => $key) {
      $keywords .= "$key[cKeyword], ";
    }
    $keywords = rtrim($keywords, ', ');
  } 

  if($count < 10){
    $sql =  'SELECT DISTINCT tKeyword.cKeyword FROM tSearchHistory 
            INNER JOIN tKeyword ON tSearchHistory.nKeywordID = tKeyword.nKeywordID 
            WHERE tSearchHistory.nUserID != :user_ID AND tKeyword.cKeyword NOT IN (:keywords)
            ORDER BY tSearchHistory.nSearches DESC 
            LIMIT :limit';

    $q = $db->prepare($sql);

    $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    $q->bindValue(':limit', $limit, PDO::PARAM_INT);
    $q->bindValue(':keywords', $keywords);

    $q->execute();

    $popular_keywords = $q->fetchAll();
    
    $result = array_merge($result, $popular_keywords);
  }

  $keywords = ""; 

  if( $result ){
    foreach ($result as $param => $key) {
      $keywords .= "$key[cKeyword] ";
    }
    $keywords = rtrim($keywords, ' ');
  } 
  
} catch(Exception $ex) {
  _res(500, ["info"=>"System under maintainance", "error"=>__LINE__]);
}

// Query for limit of pins to load
try {
  $count = false;

  if($_GET['limit_start'] === "0"){
    $sql = 'SELECT COUNT(DISTINCT tPin.nPinID) as count 
            FROM tPin
            INNER JOIN tUser creator ON tPin.nCreatorID = creator.nUserID
            INNER JOIN tFollowership ON creator.nUserID = tFollowership.nFolloweeID
            WHERE (creator.bActive = 1 AND tPin.bActive = 1 AND tPin.nCreatorID != :user_ID) 
            AND (
                  (tFollowership.bActive = 1 AND tFollowership.nFollowerID = :user_ID)
                  OR
                  MATCH(tPin.cTitle, tPin.cDescription) AGAINST(:keywords IN NATURAL LANGUAGE MODE)
                )
            ';

    $q = $db->prepare($sql);
    $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
    $q->bindValue(':keywords', $keywords);

    $q->execute();

    $count = $q->fetch();
  }

} catch (Exception $ex) {
  _res(500, ["info"=>"System under maintainance", "error"=>__LINE__]);
}

// Query for pins
try {
  $sql = 'SELECT DISTINCT
                  tPin.nPinID, 
                  tPin.cFileName, 
                  tPin.cFileExtension, 
                  tPin.cTitle, 
                  tPin.cDescription, 
                  tPin.cURL, 
                  tPin.nCreatorID, 
                  creator.cUsername, 
                  creator.cImage
        FROM tPin
        INNER JOIN tUser creator ON tPin.nCreatorID = creator.nUserID
        INNER JOIN tFollowership ON creator.nUserID = tFollowership.nFolloweeID
        WHERE (creator.bActive = 1 AND tPin.bActive = 1 AND tPin.nCreatorID != :user_ID) 
        AND (
              (tFollowership.bActive = 1 AND tFollowership.nFollowerID = :user_ID) 
              OR
              MATCH(tPin.cTitle, tPin.cDescription) AGAINST(:keywords IN NATURAL LANGUAGE MODE)
            )
        ORDER BY tPin.dCreated DESC
        LIMIT :limit_start, :limit_interval';

  $q = $db->prepare($sql);

  $q->bindValue(':limit_start', $_GET['limit_start'], PDO::PARAM_INT);
  $q->bindValue(':limit_interval', $_GET['limit_interval'], PDO::PARAM_INT);
  $q->bindValue(':user_ID', $_SESSION['user']['nUserID']);
  $q->bindValue(':keywords', $keywords);

  $q->execute();

  $result = $q->fetchAll();
  shuffle($result);

  if( ! $result ){
    _res(400, ['info'=>'Failed to load all pins', 'error'=>__LINE__, 'data'=> []]);
  }

  _res(200, $count ? 
            ['info'=>'Loading pins for home feed', 'limit'=>$count["count"], 'data' => $result] 
            : ['info'=>'Loading pins for home feed', 'data' => $result]);

} catch (Exception $ex) {
  _res(500, ["info"=>"System under maintainance", "error"=>__LINE__]);
}