<?php
/**
 * Created by PhpStorm.
 * User: Vitalik
 * Date: 17.03.2019
 * Time: 21:06
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\DB;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;

class RegisterCommand extends UserCommand
{
    protected $name = 'register';                      // Your command's name
    protected $description = 'Регистрирует вас на сайте auth-project.ru. Введите логин и пароль через пробел после названия команды'; // Your command description
    protected $usage = '/register';                    // Usage of your command
    protected $version = '1.0.0';                  // Version of your command

    public function execute()
    {
        $message = $this->getMessage();            // Get Message object

        $chat_id = $message->getChat()->getId();   // Get the current Chat ID

        $userId = $message->getFrom()->getId();

        $text = $message->getText(true);

        $registerData = $this->getRegisterData($text);

        if ($registerData === false) {
            $text = "Ошибка, неверно заполнены поле(поля) регистрации или такой логин уже существует\n" .
                "Логин должен быть не короче 6 символов, состоять только из латинских букв, цифр и символом нижнего подчеркивания.\n" .
                "Пароль должен быть не короче 8 символов.\n" .
                "Логин и пароль должны быть разделены пробелом";
        } else {

            $login = $registerData['login'];
            $password = $registerData['password'];

            $isRegistered = $this->registerUser($login, $password, $userId);

            if ($isRegistered) {
                $text = "Вы зарегистрированы на сайте auth-project.ru.\n" .
                    "Логин: $login\n" .
                    "Пароль: $password";
            } else {
                $text = "Ошибка";
            }
        }

        $data = [                                  // Set up the new message data
            'chat_id' => $chat_id,                 // Set Chat ID to send the message to
            'text' => $text, // Set message to send
        ];

        return Request::sendMessage($data);        // Send message!
    }

    /**
     * Регистрирует пользователя
     * @param string $login
     * @param string $password
     * @param int $userId
     *
     * @return bool
     */
    private function registerUser($login, $password, $userId, $isConfirmed = 1)
    {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // запись в БД
        $sql = 'INSERT INTO site_user (login, user_id, password_hash, is_confirmed) VALUES (:login, :user_id ,:password_hash, :is_confirmed)';

        $PDO = DB::getPdo();
        $result = $PDO->prepare($sql);
        $result->bindParam(':user_id', $userId, $PDO::PARAM_INT);
        $result->bindParam(':login', $login, $PDO::PARAM_STR);
        $result->bindParam(':password_hash', $password_hash, $PDO::PARAM_STR);
        $result->bindParam(':is_confirmed', $isConfirmed, $PDO::PARAM_INT);

        return $result->execute();
    }

    /**
     * Получает данные для регистрации из сообщения
     *
     * @param $text
     * @return array|bool
     */
    private function getRegisterData($text) {

        if ($text == '') {
            return false;
        }

        $data = explode(' ', $text);

        if (count($data) != 2) {
            return false;
        }
        $login = $text[0];
        $password = $text[1];

        if ($this->checkLogin($login) === false || $this->checkPassword($password) === false) {
            return false;
        }

        return [
            'login' => $login,
            'password' => $password
        ];
    }

    /**
     * Проверяет валидность логина:
     * - минимальное число символов равно 6
     *
     * @param string $login
     * @return bool
     */
    private function checkLogin($login) {

        // проверка длины логина
        if (mb_strlen($login) < 6) {
            return false;
        }

        // проверка валидности символов в логине
//        if (preg_match('/[^[:alnum:]_]/', $login)) {
//            return false;
//        }

        // проверка на существование логина в БД
        $PDO = DB::getPdo();

        $sql = 'SELECT count(*) as count FROM site_user WHERE login = :login';
        $query = $PDO->prepare($sql);
        $query->bindParam(':login', $login, $PDO::PARAM_STR);
        $query->execute();

        $row = $query->fetch($PDO::FETCH_ASSOC);

        if ($row['count'] != 0) {
            return false;
        }

        return true;
    }

    private function checkPassword($password)
    {
        // проверка длины пароля - 8 символом
        if (mb_strlen($password) < 8) {
            return false;
        }

        return true;
    }
}