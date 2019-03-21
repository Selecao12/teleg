<?php
/**
 * Created by PhpStorm.
 * User: Vitalik
 * Date: 22.03.2019
 * Time: 1:47
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\DB;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;

class ShowmyaccountsCommand extends UserCommand
{
    protected $name = 'showmyaccounts';                      // Your command's name
    protected $description = 'Показывает ваши аккаунты на auth-project.ru'; // Your command description
    protected $usage = '/showmyaccounts';                    // Usage of your command
    protected $version = '1.0.0';                  // Version of your command

    public function execute()
    {
        $message = $this->getMessage();            // Get Message object
        $chatId = $message->getChat()->getId();   // Get the current Chat ID
        $userId = $message->getFrom()->getId();

        $accounts = $this->selectAccounts($userId);

        $text = "Ваши аккаунты на auth-project.ru\n" . implode("\n", $accounts);

        $data = [                                  // Set up the new message data
            'chat_id' => $chatId,                 // Set Chat ID to send the message to
            'text' => $text, // Set message to send
        ];

        return Request::sendMessage($data);        // Send message!
    }

    private function selectAccounts($userId)
    {

        // получение аккаунтов пользователя с данным user_id
        $PDO = DB::getPdo();

        $sql = 'SELECT login FROM site_user WHERE user_id = :user_id';
        $query = $PDO->prepare($sql);
        $query->bindParam(':user', $userId, $PDO::PARAM_INT);
        $query->execute();

        $row = $query->fetchAll();

        return $row;
    }
}