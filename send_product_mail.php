<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Allow Live Server (and other local origins) to post during development
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin !== '' && (
    preg_match('#^https?://(127\.0\.0\.1|localhost)(:\d+)?$#', $origin) ||
    preg_match('#^https?://risagrobotics\.local(:\d+)?$#', $origin)
)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    header('Vary: Origin');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Load PHPMailer via Composer
require 'vendor/autoload.php';

// Your secret key
$recaptchaSecret = '6Ld-GlArAAAAAFsEluMpa3qN2s2a3y6X0xEK1x59';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if reCAPTCHA response is present
    if (!isset($_POST['g-recaptcha-response'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'reCAPTCHA verification failed. Please try again.'
        ]);
        exit;
    }

    // Verify reCAPTCHA with Google
    $recaptchaResponse = $_POST['g-recaptcha-response'];
    $userIP = $_SERVER['REMOTE_ADDR'];
    $verifyURL = "https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$recaptchaResponse}&remoteip={$userIP}";
    
    $response = @file_get_contents($verifyURL);
    $responseKeys = json_decode($response, true);

    if (!is_array($responseKeys) || empty($responseKeys['success'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'reCAPTCHA validation failed. Please confirm you are not a robot.'
        ]);
        exit;
    }


    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name    = isset($_POST['name']) ? trim($_POST['name']) : '';
        $email   = isset($_POST['email']) ? trim($_POST['email']) : '';
        $phone   = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $product = isset($_POST['product']) ? trim($_POST['product']) : '';

        if ($name === '' || !preg_match('/^[A-Za-z]+(?:\s+[A-Za-z]+)*$/', $name)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Name must contain only letters and spaces.'
            ]);
            exit;
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please enter a valid email address.'
            ]);
            exit;
        }

        if ($phone === '' || !preg_match('/^\+?[0-9]+$/', $phone)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Phone number must contain only digits (optional + for country code).'
            ]);
            exit;
        }
        // $service   = $_POST['service'];
        // $message = $_POST['message'];
        $subject = 'New product Enquiry From Risag';


        // $subject   = $_POST['form_subject'];
        // $message = $_POST['form_message'];

        $mail = new PHPMailer(true);

        try {
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'sales@yesmachinery.ae'; 
            $mail->Password   = 'objvkupdqixgjovn'; 
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            // Recipients — Gmail requires From to match the authenticated account
            $mail->setFrom('info@risag.ae', $name ?: 'Risag Website');
            $mail->addReplyTo($email, $name);
            $mail->addAddress('info@risag.ae');

            $mail->isHTML(true);
            $mail->Subject = 'New product Enquiry From Risag';
            // $mail->Body = '
            //     <div style="max-width:600px;margin:0 auto;border:1px solid #ddd;padding:20px;font-family:sans-serif;background:#f9f9f9;">
            //         <h2 style="color:#333;border-bottom:1px solid #ddd;padding-bottom:10px;">📩 ' .  htmlspecialchars($subject) . '</h2>
            //         <table cellpadding="10" cellspacing="0" width="100%" style="color:#333;">
            //             <tr>
            //                 <td width="30%"><strong>Name:</strong></td>
            //                 <td>' . htmlspecialchars($name) . '</td>
            //             </tr>
            //             <tr>
            //                 <td><strong>Email:</strong></td>
            //                 <td>' . htmlspecialchars($email) . '</td>
            //             </tr>
            //             <tr>
            //                 <td><strong>Phone:</strong></td>
            //                 <td>' . htmlspecialchars($phone) . '</td>
            //             </tr>
            //             <tr>
            //                 <td><strong>Product Name:</strong></td>
            //                 <td>' . htmlspecialchars($product) . '</td>
            //             </tr>
                    
                       
            //         </table>
            //         <p style="margin-top:30px;font-size:12px;color:#888;">This message was sent from the contact form on <a href="https://risag.com" style="color:#0066cc;">risag.com</a>.</p>
            //     </div>
            // ';
            $message = isset($_POST['message']) ? trim($_POST['message']) : 'Product Enquiry';
            $product = isset($_POST['product']) ? trim($_POST['product']) : '';
            $productRow = '';
            if (!empty($product)) {
                $productRow = 
                '<tr>
                    <td  style="text-align: left; padding-left: 30px;padding-bottom: 9px; font-size: 14px;"><strong>Product Intrested :</strong></td>
                    <td>' . htmlspecialchars($product) . '</td>
                </tr>';
            }
            $template = file_get_contents('email-template.php');
            $replacements = [
                '{{name}}' => htmlspecialchars($name),
                '{{email}}' => htmlspecialchars($email),
                '{{phone}}' => htmlspecialchars($phone),
                // '{{product}}' => htmlspecialchars($product),
                '{{productRow}}' => $productRow,
                '{{message}}' => htmlspecialchars($message),
                '{{subject}}' => htmlspecialchars($subject),
            ];

            foreach ($replacements as $key => $value) {
                $template = str_replace($key, $value, $template);
            }

            $mail->Body = $template;
            $mail->send();
            // echo 'Message sent successfully.';
            echo json_encode([
                'status' => 'success',
                'message' => 'Mail sent successfully.'
            ]);
        } catch (Exception $e) {
            // echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            echo json_encode([
                'status' => 'error',
                'message' => "Message could not be sent. Error: {$mail->ErrorInfo}"
            ]);
        }
    }
}
