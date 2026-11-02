<?php
// mailer.php (LOCAL DEVELOPMENT VERSION)

function sendOTPEmail($toEmail, $name, $otp, $role = 'User') {
    // Format it nicely
    $logContent = "Role: $role\n";
    $logContent .= "Email: $toEmail\n";
    $logContent .= "Name: $name\n";
    $logContent .= "OTP: $otp\n";
    $logContent .= "Time: " . date('Y-m-d H:i:s') . "\n";
    $logContent .= "-------------------\n";
    
    // Save it to the text file
    file_put_contents(__DIR__ . '/local_otps.txt', $logContent, FILE_APPEND);
    
    return true;
}
?>