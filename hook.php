<?php
/**
 * Created by PhpStorm.
 * User: Vitalik
 * Date: 11.03.2019
 * Time: 20:35
 */

require_once __DIR__ . "/vendor/autoload.php";

// Получите токен у бота @BotFather
$API_KEY = '<token>';

// Получите свой User ID у бота @MyTelegramID_bot
$USER_ID = '<user_id>';

// Придумайте своему боту имя
$BOT_NAME = "<bot_name>";

// Данные базы данных
$mysql_credentials = [
    'host'     => '<host>',
    'user'     => '<user>',
    'password' => '<password>',
    'database' => '<database>',
];

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\TelegramLog;

try {
    // Инициализация бота
    $telegram = new Telegram($API_KEY, $BOT_NAME);

    // Подключение базы данных
    $telegram->enableMySQL($mysql_credentials);

    // Добавление папки commands,
    // в которой будут лежать ваши личные комманды
    $telegram->addCommandsPath(__DIR__ . "/commands");

    // Добавление администратора бота
    $telegram->enableAdmin((int)$USER_ID);

    // Включение логов
    TelegramLog::initUpdateLog($BOT_NAME . '_update.log');

    // Опционально. Здесь вы можете указать кастомный объект update,
    // чтобы поймать ошибки через var_dump.
    //$telegram->setCustomInput("");

    // Основной обработчик событий
    $telegram->handle();

} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // В случае неудачи будет выведена ошибка
    echo '<pre>';
    var_dump($e);
}
