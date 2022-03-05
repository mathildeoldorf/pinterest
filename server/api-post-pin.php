<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();

// Authenticate CSRF token to proceed
_csrf_auth();

if ( ! $_FILES ){ _res(400, ['info'=>'Image is required', 'error'=>__LINE__]); }
if ( ! isset($_FILES['image']) || empty($_FILES['image']) ){ _res(400, ['info'=>'Image is required', 'error'=>__LINE__]); }

if ( ! isset($_POST['title']) || empty($_POST['title']) ){ _res(400, ['info'=>'Title is required', 'error'=>__LINE__]); }
if ( strlen($_POST['title'] ) < _TITLE_MIN_LENGTH ){ _res(400, ['info'=>'Title is too short', 'error'=>__LINE__]); }
if ( strlen($_POST['title'] ) > _TITLE_MAX_LENGTH ){ _res(400, ['info'=>'Title is too long', 'error'=>__LINE__]); }

if( empty($_POST['description']) ){ unset($_POST['description']); }
if ( isset($_POST['description']) ){
    if ( strlen($_POST['description']) < _DESCRIPTION_MIN_LENGTH ){ _res(400, ['info'=>'Description is too short', 'error'=>__LINE__]); }
    if ( strlen($_POST['description'] ) > _DESCRIPTION_MAX_LENGTH ){ _res(400, ['info'=>'Description is too long', 'error'=>__LINE__]); }
}

if ( empty($_POST['url']) ){ unset($_POST['url']); }
if ( isset($_POST['url']) ){
    if ( strlen($_POST['url'] ) < _URL_MIN_LENGTH ){ _res(400, ['info'=>'URL is too short', 'error'=>__LINE__]); }
    if ( strlen($_POST['url'] ) > _URL_MAX_LENGTH ){ _res(400, ['info'=>'URL is too long', 'error'=>__LINE__]); }
    if ( ! filter_var($_POST['url'], FILTER_VALIDATE_URL) ) { _res(400, ['info'=>'Invalid format - URL', 'error'=>__LINE__]); }
} 

$file = $_FILES['image'];

if ( is_array($file['name']) ){ _res(400, ['info'=>'You can only upload one image', 'error'=>__LINE__]); }

// // (Byte to KB = Byte/1024) | (Byte to MB = (Byte/1024)/1024)
if ( $file['size'] > _MB * 10 ){ _res(400, ['info'=>'The file size of the image is too large. Max. size is 10 MB', 'error'=>__LINE__]); }
if ( $file['size'] < _KB * 10 ){ _res(400, ['info'=>'The file size of the image is too small. Min. size is 10 KB', 'error'=>__LINE__]); }

$file_extension = pathinfo( $file['name'], PATHINFO_EXTENSION );
$file_name = uniqid();
// is it better to add the actual filename, to not have multiple of the same image?
// $file_name = pathinfo( $file['name'], PATHINFO_FILENAME );
$path = __DIR__."/images/pins/$file_name.$file_extension";

if ( ! in_array( $file_extension, ['png', 'jpeg', 'jpg'] ) ){ _res(400, ['info'=>'The image is not valid. The file must be JPG JPEG or PNG', 'error'=>__LINE__]); }

// the list of allowed parameters and field names
$allowed_fields = [
    'title' => 'cTitle',
    'description' => 'cDescription', 
    'url' => 'cURL',
    'file_name' => 'cFileName',
    'file_extension' => 'cFileExtension',
    'image' => 'cImage'
];

// Initialize an array to contain the param and the value posted:
$params = [];

// Initialize a string with `fieldname` = :placeholder pairs
$set = "";
$values = "";
$columns = "";

// Initialize array to contain data to send back to the frontend
$data = [];

// Loop over $_POST data array using the $allowed_fields
foreach ($allowed_fields as $param => $key) {
    if ( isset( $_POST[$param] ) && $param !== 'image'){
        $columns .= "$key,";
        $values .= ":$param,";

        // $set .= "`$key` = :$param,";
        $params[$param] = $_POST[$param];
        $data[$key] = $_POST[$param];
    }
    if( isset( $_FILES['image'] ) && ( $param ===  'file_extension' || $param ===  'file_name' ) ){
        // $set .= "`$key` = :$param,";
        $columns .= "$key,";
        $values .= ":$param,";
        if( $param ===  'file_extension' ){ 
            $params[$param] = $file_extension; 
        }
        if( $param ===  'file_name' ){ 
            $params[$param] = $file_name; 
        }
    }

}

$columns .= "nCreatorID";
$values .= ":user_ID";

$set = rtrim($set, ',');
$columns = rtrim($columns, ',');
$values = rtrim($values, ',');

$params['user_ID'] = $_SESSION['user']['nUserID'];


// // DB connection
$db = _db();

// Query
try {
    // TODO: QUESTIONS : How to handle not required fields like url and description?
    $sql = "INSERT INTO tPin ($columns) VALUES ($values)";
    $q = $db->prepare($sql);

    $q->execute($params);
    
    move_uploaded_file($file['tmp_name'], $path); // temp path, path

    // QUESTIONS: Is it better to do a select?
    _res(200, [ 'info'=>'Pin created succesfully', 
                'data'=>[
                    'nPinID'            => $db->lastInsertId(),
                    'cTitle'            => $_POST['title'],
                    'cFileName'         => $file_name,
                    'cFileExtension'    => $file_extension,
                    'cDescription'      => isset($_POST['description']) ? $_POST['description'] : null,
                    'cURL'              => isset($_POST['url']) ? $_POST['url'] : null,
                    'nCreatorID'        => $_SESSION['user']['nUserID'],
                    'cUsername'         => $_SESSION['user']['cUsername'],
                    'cImage'            => $_SESSION['user']['cImage'],
                    'nComments'         => 0,
                    'aComments'         => [],
                    'nLikes'         => 0,
                    'aLikes'         => [],
                ]
    ]);
} catch (Exception $ex) {
    _res(500, ['info'=>$ex, 'error'=>__LINE__]);
}