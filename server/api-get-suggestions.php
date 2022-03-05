<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();

if ( ! isset($_GET['keyword']) || empty($_GET['keyword']) ){ _res(400, ['info'=>'Keyword is required', 'error'=>__LINE__]); }
if ( strlen($_GET['keyword'] ) < _TERM_MIN_LENGTH ){ _res(400, ['info'=>'Keyword is too short. Min. length is 3 characters.', 'error'=>__LINE__]); }
if ( strlen($_GET['keyword'] ) > _TERM_MAX_LENGTH ){ _res(400, ['info'=>'Keyword is too is too long. Max. length is 50 characters', 'error'=>__LINE__]); }

// DB connection
$db = _db();

$keywords = explode(" ", $_GET['keyword']);
$suggestions = [];

// Query
try {
    foreach($keywords as $keyword){
        $sql =    'SELECT 
        tKeyword.cKeyword, tKeyword.nKeywordID,
        MATCH(tKeyword.cKeyword) AGAINST (:keyword IN BOOLEAN MODE) AS nRelevance
        FROM tKeyword
        WHERE
        MATCH(tKeyword.cKeyword) AGAINST(:keyword IN BOOLEAN MODE)
        ORDER BY nRelevance DESC
        LIMIT 10';

        $q = $db->prepare($sql);
        $q->bindValue(':keyword', "$keyword*");

        $q->execute();
        $result = $q->fetchAll();

        if( $result ){
            foreach($result as $suggestion){
                $suggestions[] = $suggestion;
            }
        }
    }

    if( count($suggestions) === 0 ){
        _res(404, ['info'=>"No suggestions related to your search: ", 'keyword'=>$_GET['keyword'], 'data'=>[]]);
    }

    _res(200, ['info'=>"Showing suggestions related to your search: $_GET[keyword]", 'data' => $suggestions]);
    
} catch(Exception $ex) {
    var_dump($ex);
    _res(500, ["info"=>"system under maintainance", "error"=>__LINE__]);
}