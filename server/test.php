<?php
define('MY_NAME', 'Mathilde');

// try {
//     echo MY_NAME;
// } catch (Exception $ex) {
//     echo 'error';
// }

// echo $name;

function test () {
    echo MY_NAME;
}
test();


// test();
// echo test();
// echo $lastname;

// Super globals (inherit) - $_POST, $_GET, $_SESSION, __LINE__, __DIR__ etc. 
    // exist within the scope of the project
    // Mutable

// Constant - Semi super global - defined by using: define('_NAME_MIN_LENGTH', 2); - custom super global
    // exist within the scope of document - also in functions
    // behaving as a super global as it can be accessed throughout the document even in the scope of functions
    // Imutable
    // needs to be important

// Globals declared outside a function are variables exist within the scope of file
    // Globals cannot be accessed inside a function, except if the global keyword is used (prepended)
// Globals declared inside a function exist within the scope of the function
    // They can be passed to the scope outside the function if returned by that function
