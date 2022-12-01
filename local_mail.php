<?php
require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';
require_once "ids.php";


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function local_mail(
                $destination_addresses,
                $subject,
                $body,
            ) {
    
    $mail = new PHPMailer(true); // Passing `true` enables exceptions

    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->Host       = SMTP_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_username;
        $mail->Password   = SMTP_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = SMTP_port;

        // Recipients
        $mail->setFrom(SMTP_from_email, SMTP_from_name);
        $destination_addresses = explode(",", $destination_addresses);
        /*
        foreach($destination_addresses as $address) {
            $mail->addAddress($address);
        }*/
        $mail->addAddress("oliv@iobook.net");

        // Content
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
