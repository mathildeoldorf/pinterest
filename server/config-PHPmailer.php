<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
// Require files from PHPMailer
require __DIR__.'/libs/PHPMailer/PHPMailer.php';
require __DIR__.'/libs/PHPMailer/Exception.php';
require __DIR__.'/libs/PHPMailer/SMTP.php';

// Instantiation and passing `true` enables exceptions/errors
$mail = new PHPMailer(true);
//Server settings
$mail->SMTPDebug = 0;                                       // Enable verbose debug output
$mail->isSMTP();                                            // Set mailer to use SMTP
// $mail->Host       = 'smtp.gmail.com';                       // Specify main and backup SMTP servers
$mail->Host       = 'server295.web-hosting.com';                       // Specify main and backup SMTP servers
$mail->SMTPAuth   = true;                                   // Enable SMTP authentication
// $mail->Username   = 'mathilde.kea.test@gmail.com';          // SMTP username
$mail->Username   = 'pinterest@fiostudio.com';          // SMTP username
// $mail->Password   = 'keatest2019';                          // SMTP password
$mail->Password   = 'NOBYl(JG{ZA7';                          // SMTP password
$mail->SMTPSecure = 'tls';                                  // Enable TLS encryption, `ssl` also accepted
$mail->Port       = 587;                                    // TCP port to connect to
$mail->setFrom('pinterest@fiostudio.com', 'Pinterest');
// $mail->setFrom('mathilde.kea.test@gmail.com', 'Pinterest');
$mail->isHTML(true);                                  // Set email format to HTML
