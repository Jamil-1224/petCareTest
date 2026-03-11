<?php
require_once __DIR__ . '/vendor/autoload.php';

use Twilio\Rest\Client;

/**
 * TWILIO CONFIGURATION TEMPLATE
 * ==============================
 * 
 * 1. Copy this file to: twilio_helper.php
 * 2. Update the credentials below with your actual Twilio account details
 * 3. Never commit twilio_helper.php to Git (it's in .gitignore)
 * 
 * Get your credentials from: https://console.twilio.com/
 */

/**
 * Normalize phone number to E.164-ish format.
 * If number starts with 0 or lacks +, prepend default country code.
 */
function normalize_phone_number($number, $default_country = '+1')
{
    $n = trim($number);
    // remove common formatting characters
    $n = preg_replace('/[^\d\+]/', '', $n);

    if ($n === '') return '';

    // If starts with +, assume already in E.164
    if (strpos($n, '+') === 0) {
        return $n;
    }

    // If starts with 00, convert to +
    if (strpos($n, '00') === 0) {
        return '+' . substr($n, 2);
    }

    // If starts with 0 -> local national format, replace leading 0 with default country (e.g. +880)
    if (strpos($n, '0') === 0) {
        return $default_country . substr($n, 1);
    }

    // If it looks like it already has country code (e.g. 880...) but no plus
    if (strlen($n) > 8 && substr($n, 0, strlen(ltrim($default_country, '+'))) === ltrim($default_country, '+')) {
        return '+' . $n;
    }

    // Fallback: prefix default country
    return $default_country . $n;
}

/**
 * Send SMS using Twilio. Returns true on success, false on failure.
 * Credentials are read from environment variables if present:
 * TWILIO_SID, TWILIO_TOKEN, TWILIO_FROM, TWILIO_DEFAULT_COUNTRY
 */
function send_sms($to_number, $message)
{
    // ⚠️ REPLACE THESE WITH YOUR ACTUAL TWILIO CREDENTIALS ⚠️
    $sid    = getenv('TWILIO_SID') ?: 'YOUR_TWILIO_ACCOUNT_SID';
    $token  = getenv('TWILIO_TOKEN') ?: 'YOUR_TWILIO_AUTH_TOKEN';
    $from   = getenv('TWILIO_FROM') ?: 'YOUR_TWILIO_PHONE_NUMBER';  // E.g., +14155551234
    $default_country = getenv('TWILIO_DEFAULT_COUNTRY') ?: '+880'; // Bangladesh country code (change to your country)

    // Basic validation
    if (empty($to_number) || empty($message)) {
        return false;
    }

    $to = normalize_phone_number($to_number, $default_country);

    // Final check
    if (strlen(preg_replace('/\D/', '', $to)) < 6) {
        // obviously invalid
        error_log(date('Y-m-d H:i:s') . " - Invalid phone after normalization: {$to_number} -> {$to}\n", 3, __DIR__ . '/twilio_errors.log');
        return false;
    }

    // Try using Twilio SDK if available
    try {
        $client = new Client($sid, $token);
        $params = ['from' => $from, 'body' => $message];
        $msg = $client->messages->create($to, $params);
        if (!empty($msg->sid)) {
            return true;
        }
    } catch (\Throwable $e) {
        // SDK failed or thrown an exception, log and fall back to cURL
        error_log(date('Y-m-d H:i:s') . " - Twilio SDK error sending to {$to}: " . $e->getMessage() . "\n", 3, __DIR__ . '/twilio_errors.log');
    }

    // Fallback: raw HTTP POST to Twilio API
    $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
    $data = [
        'From' => $from,
        'To' => $to,
        'Body' => $message
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "{$sid}:{$token}");
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($status == 200 || $status == 201) {
        return true;
    } else {
        // Log the error
        error_log(date('Y-m-d H:i:s') . " - Twilio HTTP error sending to {$to}: HTTP {$status} Response: {$response} CurlErr: {$curl_err}\n", 3, __DIR__ . '/twilio_errors.log');
        return false;
    }
}
