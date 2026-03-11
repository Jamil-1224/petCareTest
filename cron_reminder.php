<?php
/**
 * CRON JOB REMINDER CHECKER
 * =========================
 * This file is designed to be called by a cron job every 1-2 minutes
 * It works independently of browser/JavaScript - true server-side automation
 * 
 * SETUP INSTRUCTIONS:
 * 
 * Option 1: Windows Task Scheduler
 * ---------------------------------
 * 1. Open Task Scheduler (taskschd.msc)
 * 2. Create Basic Task
 * 3. Trigger: Every day, repeat every 2 minutes
 * 4. Action: Start a program
 * 5. Program: C:\xampp\php\php.exe (your PHP path)
 * 6. Arguments: C:\path\to\petCare\cron_reminder.php
 * 
 * Option 2: cPanel / Linux Cron
 * ------------------------------
 * Add this to crontab:
 * */2 * * * * /usr/bin/php /path/to/petCare/cron_reminder.php
 * (Runs every 2 minutes)
 * 
 * Option 3: Manual Test
 * ---------------------
 * Visit: http://localhost/petCare/cron_reminder.php
 * (Shows output for testing)
 */

// Prevent browser caching
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

require __DIR__ . '/functions.php';

// Set your timezone
date_default_timezone_set('Asia/Dhaka');

global $db;

// Simple output for viewing results
$output = [];
$output[] = "=== PetCare Reminder Cron Job ===";
$output[] = "Started at: " . date('Y-m-d H:i:s');
$output[] = "";

try {
    $current_datetime = date('Y-m-d H:i:s');
    $current_timestamp = strtotime($current_datetime);
    
    // Get pending reminders with user and pet info
    $pending_reminders = $db->reminders->aggregate([
        ['$match' => ['status' => 'pending']],
        ['$lookup' => [
            'from' => 'users',
            'localField' => 'user_id',
            'foreignField' => '_id',
            'as' => 'user'
        ]],
        ['$lookup' => [
            'from' => 'pets',
            'localField' => 'pet_id',
            'foreignField' => '_id',
            'as' => 'pet'
        ]],
        ['$unwind' => '$user'],
        ['$unwind' => '$pet']
    ])->toArray();
    
    $output[] = "Found " . count($pending_reminders) . " pending reminders";
    $output[] = "";
    
    // Check if Twilio is available
    $twilio_available = file_exists(__DIR__ . '/twilio_helper.php');
    if ($twilio_available) {
        require_once __DIR__ . '/twilio_helper.php';
        $output[] = "SMS: Enabled (Twilio)";
    } else {
        $output[] = "SMS: Disabled (twilio_helper.php not found)";
    }
    $output[] = "";
    
    $completed_count = 0;
    $sms_sent_count = 0;
    $sms_failed_count = 0;
    
    foreach ($pending_reminders as $reminder) {
        try {
            // Get reminder date/time
            $reminderDateObj = $reminder['reminder_date'];
            $phpDateTime = $reminderDateObj->toDateTime();
            $phpDateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));
            
            $reminder_date = $phpDateTime->format('Y-m-d');
            $reminder_time = $reminder['reminder_time'] ?? '00:00:00';
            $reminder_datetime = $reminder_date . ' ' . $reminder_time;
            $reminder_timestamp = strtotime($reminder_datetime);
            
            $output[] = "Checking: {$reminder['title']}";
            $output[] = "  Due: {$reminder_datetime}";
            
            // Check if time has passed
            if ($current_timestamp >= $reminder_timestamp) {
                $output[] = "  ⏰ TIME REACHED - Processing...";
                
                // Send SMS if enabled and not already sent
                $sms_already_sent = isset($reminder['sms_sent']) && $reminder['sms_sent'] === true;
                
                // Extra safety: Check if SMS was sent in last 5 minutes
                if ($sms_already_sent && isset($reminder['sms_sent_date'])) {
                    $sms_sent_time = $reminder['sms_sent_date']->toDateTime()->getTimestamp();
                    if (($current_timestamp - $sms_sent_time) < 300) {
                        $output[] = "  ⏩ SMS already sent recently";
                        $sms_already_sent = true;
                    }
                }
                
                if (
                    $twilio_available &&
                    isset($reminder['send_sms']) && $reminder['send_sms'] === true &&
                    !$sms_already_sent
                ) {
                    $user_phone = $reminder['user']['phone'] ?? '';
                    
                    if (!empty($user_phone)) {
                        // Mark as sent FIRST (prevents duplicates from race conditions)
                        mongoUpdate(
                            'reminders',
                            ['_id' => $reminder['_id']],
                            ['$set' => [
                                'sms_sent' => true,
                                'sms_sent_date' => getCurrentDateTime(),
                                'sms_status' => 'sending'
                            ]]
                        );
                        
                        $pet_name = $reminder['pet']['pet_name'] ?? 'your pet';
                        $sms_text = "🐾 PetCare Reminder: {$reminder['title']} for {$pet_name}. Due: {$reminder_datetime}";
                        
                        try {
                            if (send_sms($user_phone, $sms_text)) {
                                mongoUpdate(
                                    'reminders',
                                    ['_id' => $reminder['_id']],
                                    ['$set' => ['sms_status' => 'sent']]
                                );
                                $sms_sent_count++;
                                $output[] = "  ✅ SMS sent to {$user_phone}";
                            } else {
                                mongoUpdate(
                                    'reminders',
                                    ['_id' => $reminder['_id']],
                                    ['$set' => ['sms_status' => 'failed']]
                                );
                                $sms_failed_count++;
                                $output[] = "  ❌ SMS failed to {$user_phone}";
                            }
                        } catch (Exception $e) {
                            mongoUpdate(
                                'reminders',
                                ['_id' => $reminder['_id']],
                                ['$set' => ['sms_status' => 'error']]
                            );
                            $sms_failed_count++;
                            $output[] = "  ❌ SMS error: " . $e->getMessage();
                        }
                    } else {
                        $output[] = "  ⚠️ No phone number";
                    }
                }
                
                // Mark reminder as completed
                mongoUpdate(
                    'reminders',
                    ['_id' => $reminder['_id']],
                    ['$set' => [
                        'status' => 'completed',
                        'completed_at' => getCurrentDateTime(),
                        'updated_at' => getCurrentDateTime()
                    ]]
                );
                
                $completed_count++;
                $output[] = "  ✅ Reminder completed";
                
            } else {
                $minutes_until = round(($reminder_timestamp - $current_timestamp) / 60);
                $output[] = "  ⏱️ Not yet due ({$minutes_until} min remaining)";
            }
            
            $output[] = "";
            
        } catch (Exception $e) {
            $output[] = "  ❌ ERROR: " . $e->getMessage();
            $output[] = "";
        }
    }
    
    $output[] = "=== SUMMARY ===";
    $output[] = "Reminders completed: {$completed_count}";
    $output[] = "SMS sent: {$sms_sent_count}";
    if ($sms_failed_count > 0) {
        $output[] = "SMS failed: {$sms_failed_count}";
    }
    
} catch (Exception $e) {
    $output[] = "FATAL ERROR: " . $e->getMessage();
}

$output[] = "";
$output[] = "Finished at: " . date('Y-m-d H:i:s');
$output[] = "============================";

// Log to file
$log_content = implode("\n", $output) . "\n\n";
file_put_contents(__DIR__ . '/reminder_cron.log', $log_content, FILE_APPEND);

// Display output (for manual testing)
echo "<pre>" . htmlspecialchars($log_content) . "</pre>";

// If running from command line, output to console
if (php_sapi_name() === 'cli') {
    echo $log_content;
}
