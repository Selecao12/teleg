<?php
/**
 * Created by PhpStorm.
 * User: Vitalik
 * Date: 11.03.2019
 * Time: 23:11
 */

// Load composer
require_once __DIR__ . '/vendor/autoload.php';
// Add you bot's API key and name
$bot_api_key  = '775369493:AAG5XX1HTdFXCcA9p0HwAHGdcWAiqiuHOgg';
$bot_username = 'auth_project_bot';
// Define the URL to your hook.php file
$hook_url     = 'https://teleg.auth-project.ru/hook.php';
try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($bot_api_key, $bot_username);
    // Set webhook
    $result = $telegram->setWebhook($hook_url);
    // To use a self-signed certificate, use this line instead
    //$result = $telegram->setWebhook($hook_url, ['certificate' => $certificate_path]);
    if ($result->isOk()) {
        echo $result->getDescription();
    }
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    echo $e->getMessage();
}