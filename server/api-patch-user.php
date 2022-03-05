<?php
require_once('globals.php');

// Authenticate user to proceed
_auth();

// Authenticate CSRF token to proceed
_csrf_auth();

// Check data - skipping the empty values for not required fields
// First name
if ( empty($_POST['first_name']) ){ unset($_POST['first_name']);  }
if( ! empty($_POST['first_name']) ){
    if ( strlen($_POST['first_name'] ) < _NAME_MIN_LENGTH ){ _res(400, ['info'=>'First name is too short. Minimum 2 characters.', 'error'=>__LINE__]); }
    if ( strlen($_POST['first_name'] ) > _NAME_MAX_LENGTH ){ _res(400, ['info'=>'First name is too long. Maximum 50 characters.', 'error'=>__LINE__]); }
    if ( $_POST['first_name'] === $_SESSION['user']['cFirstName'] ){ unset($_POST['first_name']); }
}

// Last name
if ( empty($_POST['last_name']) ){ unset($_POST['last_name']);  }
if( ! empty($_POST['last_name']) ){
    if ( strlen($_POST['last_name'] ) < _NAME_MIN_LENGTH ){ _res(400, ['info'=>'Last name is too short. Minimum 2 characters.', 'error'=>__LINE__]); }
    if ( strlen($_POST['last_name'] ) > _NAME_MAX_LENGTH ){ _res(400, ['info'=>'Last name is too long. Maximum 50 characters.', 'error'=>__LINE__]); }
    if ( $_POST['last_name'] === $_SESSION['user']['cLastName'] ){ unset($_POST['last_name']); }
}

// Description
if ( empty($_POST['description']) ){ unset($_POST['description']);  }
if ( ! empty($_POST['description']) ){
    if ( strlen($_POST['description']) < _DESCRIPTION_MIN_LENGTH ){ _res(400, ['info'=>'Description is too short', 'error'=>__LINE__]); }
    if ( strlen($_POST['description'] ) > _DESCRIPTION_MAX_LENGTH ){ _res(400, ['info'=>'Description is too long', 'error'=>__LINE__]); }
    if ( $_POST['description'] === $_SESSION['user']['cDescription'] ){ unset($_POST['description']); }
}

// URL
if ( empty($_POST['url']) ){ unset($_POST['url']);  }
if ( ! empty($_POST['url']) ){
    if ( strlen($_POST['url'] ) < _URL_MIN_LENGTH ){ _res(400, ['info'=>'URL is too short', 'error'=>__LINE__]); }
    if ( strlen($_POST['url'] ) > _URL_MAX_LENGTH ){ _res(400, ['info'=>'URL is too long', 'error'=>__LINE__]); }
    if ( ! filter_var($_POST['url'], FILTER_VALIDATE_URL) ) { _res(400, ['info'=>'Invalid format - URL', 'error'=>__LINE__]); }
    if ( $_POST['url'] === $_SESSION['user']['cURL'] ){ unset($_POST['url']); }
} 

// Email
if ( empty($_POST['email']) ){ unset($_POST['email']);  }
if ( ! empty($_POST['email']) ){
    if ( ! filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ){ _res(400, ['info'=>'The e-mail is not a valid', 'error'=>__LINE__]); }
    if ( $_POST['email'] === $_SESSION['user']['cEmail'] ){ unset($_POST['email']); }    
}

// Username
if ( empty($_POST['username']) ){ unset($_POST['username']);  }
if( ! empty($_POST['username']) ){
    if ( strlen($_POST['username'] ) < _NAME_MIN_LENGTH ){ _res(400, ['info'=>'Username is too short. Minimum 2 characters.', 'error'=>__LINE__]); }
    if ( strlen($_POST['username'] ) > _NAME_MAX_LENGTH ){ _res(400, ['info'=>'Username is too long. Maximum 50 characters.', 'error'=>__LINE__]); }
    if ( $_POST['username'] === $_SESSION['user']['cUsername'] ){ unset($_POST['username']); }
}

// Profile image
if( empty($_FILES['image']['name']) ){ unset($_FILES['image']); }
if ( isset($_FILES) && isset($_FILES['image']) ){ 
    $file = $_FILES['image']; 
    if ( is_array($file['name']) ){ _res(400, ['info'=>'You can only upload one image', 'error'=>__LINE__]); }
    
    // // (Byte to KB = Byte/1024) | (Byte to MB = (Byte/1024)/1024)
    if ( $file['size'] > _MB * 10 ){ _res(400, ['info'=>'The file size of the image is too large. Max. size is 5 MB', 'error'=>__LINE__]); }
    if ( $file['size'] < _KB * 4 ){ _res(400, ['info'=>'The file size of the image is too large. Min. size is 4 KB', 'error'=>__LINE__]); }
    
    $file_name = pathinfo( $file['name'], PATHINFO_FILENAME );
    $file_extension = pathinfo( $file['name'], PATHINFO_EXTENSION );

    if ( $file['name'] === $_SESSION['user']['cImage'] ){ _res(400, ['info'=>'The image is identical to the old one', 'error'=>__LINE__]); }
    if ( ! in_array( $file_extension, ['png', 'jpeg', 'jpg'] ) ){ _res(400, ['info'=>'The image is not valid. The file must be JPG JPEG or PNG.', 'error'=>__LINE__]); }

    $path = __DIR__."/images/users/$file_name.$file_extension";
}

// If no information has been updated
if ( ! isset($_POST['first_name']) && ! isset($_POST['last_name']) && 
     ! isset($_POST['description']) && ! isset($_POST['url']) && 
     ! isset($_POST['username']) && ! isset($_FILES['image']) && 
     ! isset($_POST['email']) ){
    _res(400, ['info'=>'The information submitted is identical', 'error'=>__LINE__]);
}

// DB connection
$db = _db();

try {
    // the list of allowed parameters and field names
    $allowed_fields = [
        'first_name' => 'cFirstName',
        'last_name' => 'cLastName', 
        'description' => 'cDescription', 
        'url' => 'cURL',
        'username' => 'cUsername',
        'image' => 'cImage',
        'email' => 'cEmail'
    ];

    // Initialize an array to contain the param and the value posted:
    $params = [];
    // Initialize a string with `fieldname` = :placeholder pairs
    $set = "";
    // Initialize array to contain data to send back to the frontend
    $data = [];

    // Loop over $_POST data array using the $allowed_fields
    foreach ($allowed_fields as $param => $key) {
        if ( isset( $_POST[$param] ) && $param !== 'image'){
            $set .= "`$key` = :$param,";
            $params[$param] = $_POST[$param];
            $data[$key] = $_POST[$param];
        }
        if( isset( $_FILES['image'] ) && $param ===  'image' ){
            $set .= "`$key` = :$param,";
             $params[$param] = "$file_name.$file_extension"; 
             $data[$key] = "$file_name.$file_extension";
        }
    }
    
    $set = rtrim($set, ',');
    $params['user_ID'] = $_SESSION['user']['nUserID'];

    $sql = "UPDATE tUser SET $set WHERE nUserID = :user_ID";

    $q = $db->prepare($sql);
    $q->execute($params);

    if ( ! $q->rowCount() ){
        _res(400, ['info'=>'Failed to update your information. Please try again.', 'error'=>__LINE__]);
    }

    // Update the session to match the updated information
    foreach ($data as $key => $value) {
        $_SESSION['user'][$key] = $value;
    }

    // Move the profile image
    if( isset( $_FILES['image']) ){
        move_uploaded_file($file['tmp_name'], $path); // temp path, path
    }
    
    _res(200, [ 'info'=>'Your information was updated succesfully.', 
                'data'=> $data
    ]);

    
} catch (Exception $ex) {
    _res(500, ['info'=>'system under maintainance', 'error'=>__LINE__]);
}
