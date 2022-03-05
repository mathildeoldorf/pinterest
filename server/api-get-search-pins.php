<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();

// DB connection
$db = _db();

if ( ! isset($_GET['term']) || empty($_GET['term']) ){ _res(400, ['info'=>'Please enter a search keyword', 'error'=>__LINE__]); }
if ( strlen($_GET['term'] ) < _TERM_MIN_LENGTH ){ _res(400, ['info'=>'Search phrase is too short. Min. length is 2 characters.', 'error'=>__LINE__]); }
if ( strlen($_GET['term'] ) > _TERM_MAX_LENGTH ){ _res(400, ['info'=>'Search phrase is too long. Max. length is 50 characters', 'error'=>__LINE__]); }

// Query
try { 
        $sql = 'SELECT DISTINCT 
                tPin.nPinID, 
                tPin.cFileName, 
                tPin.cFileExtension, 
                tPin.cTitle, 
                tPin.cDescription, 
                tPin.cURL,
                tPin.bActive,

                tPin.nCreatorID, 
                creator.cUsername,
                creator.cImage,
                MATCH(tPin.cTitle, tPin.cDescription) AGAINST (:term IN NATURAL LANGUAGE MODE) AS nRelevance
                -- MATCH(tPin.cTitle, tPin.cDescription) AGAINST (:term WITH QUERY EXPANSION) AS nRelevance
                FROM tPin
                INNER JOIN tUser creator ON tPin.nCreatorID = creator.nUserID
                WHERE creator.bActive = 1 AND tPin.bActive = 1 AND (
                MATCH(tPin.cTitle, tPin.cDescription) AGAINST(:term IN NATURAL LANGUAGE MODE)
                -- MATCH(tPin.cTitle, tPin.cDescription) AGAINST(:term WITH QUERY EXPANSION)
                OR MATCH(tPin.cTitle, tPin.cDescription) AGAINST(+:term IN BOOLEAN MODE))
                ORDER BY nRelevance DESC';

  $q = $db->prepare($sql);
  $q->bindValue(':term', "$_GET[term]*");

  $q->execute();
  $result = $q->fetchAll();

  if( ! $result ){
    _res(400, ['info'=>"No results related to your search: $_GET[term].", 'error'=>__LINE__, 'data'=> []]);
  }
  
  _res(200, [ 'info'=> "Showing pins related to your search: $_GET[term]", 
              'data' => ['nResults'=> count($result), 'aResults' => $result]]);

} catch(Exception $ex) {
    var_dump($ex);
    _res(500, ["info"=>"system under maintainance", "error"=>__LINE__]);
}

