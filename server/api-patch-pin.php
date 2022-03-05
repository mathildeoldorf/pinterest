<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();

// Authenticate CSRF token to proceed
_csrf_auth();

// Check data 
if ( ! isset($_POST['pin_ID']) || empty($_POST['pin_ID']) ){ _res(400, ['info'=>'Pin ID is required', 'error'=>__LINE__]); }
if ( ! filter_var($_POST['pin_ID'], FILTER_VALIDATE_INT) ){ _res(400, ['info'=>'Invalid format - Pin ID', 'error'=>__LINE__]); }

if ( empty($_POST['title']) ){ unset($_POST['title']);  }
if ( isset($_POST['title']) ){
    if ( strlen($_POST['title'] ) < _TITLE_MIN_LENGTH ){ _res(400, ['info'=>'Title is too short', 'error'=>__LINE__]); }
    if ( strlen($_POST['title'] ) > _TITLE_MAX_LENGTH ){ _res(400, ['info'=>'Title is too long', 'error'=>__LINE__]); }
    if ( $_POST['title'] === $result['cTitle'] ){ unset($_POST['title']); }
}

if ( empty($_POST['description']) ){ unset($_POST['description']); }
if ( isset($_POST['description']) ){
    if ( strlen($_POST['description']) < _DESCRIPTION_MIN_LENGTH ){ _res(400, ['info'=>'Description is too short', 'error'=>__LINE__]); }
    if ( strlen($_POST['description'] ) > _DESCRIPTION_MAX_LENGTH ){ _res(400, ['info'=>'Description is too long', 'error'=>__LINE__]); }
    if ( $_POST['description'] === $result['cDescription'] ){ unset($_POST['description']); }
}

if ( empty($_POST['url']) ){ unset($_POST['url']); }
if ( isset($_POST['url']) ){
    if ( strlen($_POST['url'] ) < _URL_MIN_LENGTH ){ _res(400, ['info'=>'URL is too short', 'error'=>__LINE__]); }
    if ( strlen($_POST['url'] ) > _URL_MAX_LENGTH ){ _res(400, ['info'=>'URL is too long', 'error'=>__LINE__]); }
    if ( ! filter_var($_POST['url'], FILTER_VALIDATE_URL) ) { _res(400, ['info'=>'Invalid format - URL', 'error'=>__LINE__]); }
    if ( $_POST['url'] === $result['cURL'] ){ unset($_POST['url']); }
} 

// Pin image
if ( empty($_FILES['image']['name'])){ unset($_FILES['image']); }
if ( isset($_FILES) && isset($_FILES['image'])){ 
    $file = $_FILES['image']; 
    if ( is_array($file['name']) ){ _res(400, ['info'=>'You can only upload one image', 'error'=>__LINE__]); }
    
    // // (Byte to KB = Byte/1024) | (Byte to MB = (Byte/1024)/1024)
    if ( $file['size'] > _MB * 10 ){ _res(400, ['info'=>'The file size of the image is too large. Max. size is 5 MB', 'error'=>__LINE__]); }
    if ( $file['size'] < _KB * 10 ){ _res(400, ['info'=>$_FILES['image'], 'error'=>__LINE__]); }
    
    $file_name = uniqid();
    $file_name .= "-$_POST[pin_ID]";
    $file_extension = pathinfo( $file['name'], PATHINFO_EXTENSION );

    if ( $file['name'] === $_SESSION['user']['cImage'] ){ _res(400, ['info'=>'The image is identical to the old one', 'error'=>__LINE__]); }
    if ( ! in_array( $file_extension, ['png', 'jpeg', 'jpg'] ) ){ _res(400, ['info'=>'The image is not valid. The file must be JPG JPEG or PNG.', 'error'=>__LINE__]); }

    $path = __DIR__."/images/pins/$file_name.$file_extension";
}

// If no information has been updated
if ( ! isset($_POST['title']) && ! isset($_POST['description']) && ! isset($_POST['url']) && ! isset($_FILES['image']) ){
    _res(400, ['info'=>'The information submitted is identical', 'error'=>__LINE__]);
}

// DB connection
$db = _db();

// Pin is exists and is active
try {
    // the list of allowed parameters and field names
    $allowed_fields = [
        'pin_ID' => 'nPinID',
        'title' => 'cTitle', 
        'description' => 'cDescription', 
        'url' => 'cURL',
        'file_extension' => 'cFileExtension',
        'file_name' => 'cFileName'
    ];

    // Initialize an array to contain the param and the value posted:
    $params = [];

    // Initialize a string with `fieldname` = :placeholder pairs
    $set = "";

    // Initialize array to contain data to send back to the frontend
    $data = [];

    // Loop over $_POST data array using the $allowed_fields
    foreach ($allowed_fields as $param => $key) {
        
        if ( isset( $_POST[$param] ) && $param !== 'pin_ID' && ( $param !==  'file_extension' || $param !==  'file_name')){
        // if ( isset( $_POST[$param] ) && $param !== 'pin_ID' && $param !== 'image'){
            $set .= "`$key` = :$param,";
            $params[$param] = $_POST[$param];
            $data[$key] = $_POST[$param];
        }
        if( isset( $_FILES['image'] ) && ( $param ===  'file_extension' || $param ===  'file_name') ){
            $set .= "`$key` = :$param,"; 
            
            if( $param ===  'file_extension' ){ 
                $params[$param] = $file_extension; 
                $data[$key] = "$file_extension";
            }
            if( $param ===  'file_name' ){ 
                $params[$param] = $file_name; 
                $data[$key] = "$file_name";
            }
        }

    }

    
    $set = rtrim($set, ',');
    
    $params['pin_ID'] = $_POST['pin_ID'];
    $params['user_ID'] = $_SESSION['user']['nUserID'];

    $sql = "UPDATE tPin SET $set WHERE nPinID = :pin_ID AND tPin.nCreatorID = :user_ID AND tPin.bActive = 1";
    
    $q = $db->prepare($sql);
    
    $q->execute($params);

    if ( ! $q->rowCount() ){
        _res(400, ['info'=>'Failed to update pin', 'error'=>__LINE__]);
    }

    if(isset($_FILES['image'])){
        move_uploaded_file($file['tmp_name'], $path); // temp path, path
    }

    _res(200, [ 'info'=>'Pin updated succesfully', 
                'data'=> $data
    ]);

} catch (Exception $ex) {
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}