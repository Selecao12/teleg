<?php
/**
 * Created by PhpStorm.
 * User: Vitalik
 * Date: 23.03.2019
 * Time: 21:22
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\DB;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;


class ChangepasswordCommand extends UserCommand
{
    protected $name = 'changepassword';                      // Your command's name
    protected $description = 'Меняет пароль у вашего аккаунта на сайте auth-project.ru. Введите логин, старый пароль и новый пароль через пробел после названия команды.'; // Your command description
    protected $usage = '/changepassword';                    // Usage of your command
    protected $version = '1.0.0';                  // Version of your command

    protected $errors = [];

    public function execute()
    {
        $message = $this->getMessage();            // Get Message object
        $chat_id = $message->getChat()->getId();   // Get the current Chat ID
        $userId = $message->getFrom()->getId();
        $text = $message->getText(true);

        // получаем из сообщения пользователя логин, старый пароль, новый пароль
        $inputData = $this->getInputData($text);

        if (!(count($this->errors))) {
            $this->checkNewPassword($inputData['new_password']);
            $userRow = $this->getUserRow($userId, $inputData['login']);

            if (!(count($this->errors)) && $this->checkOldPassword($inputData['old_password'], $userRow['password_hash'])) {
                $this->changePassword($userId, $inputData['login'], $inputData['new_password']);
            }
        }

        if (!(count($this->errors))) {
            $text = 'Пароль для аккаунта ' . $inputData['login'] . ' изменен.';
        } else {
            $text = "Ошибка смены пароля\n" . implode("\n", $this->errors);
        }

        $data = [                                  // Set up the new message data
            'chat_id' => $chat_id,                 // Set Chat ID to send the message to
            'text' => $text, // Set message to send
        ];

        return Request::sendMessage($data);        // Send message!
    }

    /**
     * Меняет пароль в БД
     *
     * @param int $userId
     * @param string $login
     * @param string $newPassword
     *
     * @return bool
     */
    private function changePassword($userId, $login, $newPassword)
    {
        $password_hash = password_hash($newPassword, PASSWORD_DEFAULT);

        // запись в БД
        $sql = 'UPDATE site_user SET password_hash = :password_hash WHERE login = :login';

        $PDO = DB::getPdo();
        $result = $PDO->prepare($sql);
        $result->bindParam(':login', $login, $PDO::PARAM_STR);
        $result->bindParam(':password_hash', $password_hash, $PDO::PARAM_STR);

        if ($result->execute()) {
            return true;
        } else {
            $this->errors[] = 'Ошибка добавления данных в базу данных - повторите попытку.';
            return false;
        }
    }

    /**
     * Получает данные для регистрации из сообщения
     *
     * @param $text
     * @return array|bool
     */
    private function getInputData($text)
    {
        if ($text == '') {
            $this->errors[] = 'Отправлен пустой текст - вы должны указать логин, старый пароль и новый пароль через пробел после команды changepassword.';
            return false;
        }

        // разбиваем сообщение пользователя на логин и пароль
        $data = explode(' ', $text);

        if (count($data) != 3) {
            $this->errors[] = 'Неверно введены данные - вы должны указать логин, старый пароль и новый пароль через пробел после команды changepassword.';
            return false;
        }
        $login = $data[0];
        $oldPassword = $data[1];
        $newPassword = $data[2];

        return [
            'login' => $login,
            'old_password' => $oldPassword,
            'new_password' => $newPassword
        ];
    }

    /**
     * Получаем запись с таким логином
     *
     * @param int $userId
     * @param string $login
     * @return array|bool
     */
    private function getUserRow($userId, $login)
    {

        $PDO = DB::getPdo();
        $sql = 'SELECT * FROM site_user WHERE user_id = :user_id login = :login';

        $query = $PDO->prepare($sql);
        $query->bindParam(':user_id', $userId, $PDO::PARAM_STR);
        $query->bindParam(':login', $login, $PDO::PARAM_STR);
        $query->execute();

        $row = $query->fetch($PDO::FETCH_ASSOC);

        if (count($row['login']) == 0) {
            $this->errors[] = 'У вас нет аккаунта с таким логином.';
            return false;
        }

        return $row;
    }

    /**
     * Проверяет верный ли пароль ввел пользователь
     * @param string $oldPassord
     * @param string $oldPassordHash
     *
     * @return bool
     */
    private function checkOldPassword($oldPassord, $oldPasswordHash)
    {
        if (!password_verify($oldPassord, $oldPasswordHash)) {
            $this->errors[] = 'Вы ввели неверный старый пароль';
            return false;
        }

        return true;
    }

    /**
     * Проверяет валидность нового пароля
     * 
     * @param string $password
     * @return bool
     */
    private function checkNewPassword($password)
    {
        // проверка длины пароля - 8 символом
        if (mb_strlen($password) < 8) {
            $this->errors[] = 'Короткий пароль - длина пароля должна быть не менее 8 символов.';
            return false;
        }

        return true;
    }
}