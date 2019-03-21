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

    protected $errors = [];

    public function execute()
    {
        $message = $this->getMessage();            // Get Message object
        $chat_id = $message->getChat()->getId();   // Get the current Chat ID
        $userId = $message->getFrom()->getId();
        $text = $message->getText(true);

        $registerData = $this->getRegisterData($text);

        if ($registerData === false) {
            // Пользователь ввел неверные данные.
            $text = "Ошибка регистрации\n" . implode("\n", $this->errors);
        } else {

            $login = $registerData['login'];
            $password = $registerData['password'];

            $isRegistered = $this->registerUser($login, $password, $userId);

            if ($isRegistered) {
                $text = "Вы зарегистрированы на сайте auth-project.ru.\n" .
                    "Логин: $login\n" .
                    "Пароль: $password";
            } else {
                // Произошла ошибка записи в БД. Пользователь ввел верные данные.
                $text = "Ошибка\n" . implode("\n", $this->errors);
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
    private function getRegisterData($text)
    {
        if ($text == '') {
            $this->errors[] = 'Отправлен пустой текст - вы должны указать логин и пароль через пробел после команды register.';
            return false;
        }

        // разбиваем сообщение пользователя на логин и пароль
        $data = explode(' ', $text);

        if (count($data) != 2) {
            $this->errors[] = 'Неверно введены данные - вы должны указать логин и пароль через пробел после команды register.';
            return false;
        }
        $login = $data[0];
        $password = $data[1];

        if (!$this->checkLogin($login) | !$this->checkPassword($password)) {
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
    private function checkLogin($login)
    {

        // проверка длины логина
        if (mb_strlen($login) < 6) {
            $this->errors[] = 'Короткий логин - длина логина должна быть не менее 6 символов.';
            return false;
        }

        // проверка валидности символов в логине
        if (preg_match('/[^[:alnum:]_]/', $login)) {
            $this->errors[] = 'В логине присутствуют недопустимые символы - разрешается использовать только латиницу, цифры и знак нижнего подчеркивания.';
            return false;
        }

        // проверка на существование логина в БД
        $PDO = DB::getPdo();

        $sql = 'SELECT count(*) as count FROM site_user WHERE login = :login';
        $query = $PDO->prepare($sql);
        $query->bindParam(':login', $login, $PDO::PARAM_STR);
        $query->execute();

        $row = $query->fetch($PDO::FETCH_ASSOC);

        if ($row['count'] != 0) {
            $this->errors[] = 'Такой логин уже существует - придумайте новый.';
            return false;
        }

        return true;
    }

    private function checkPassword($password)
    {
        // проверка длины пароля - 8 символом
        if (mb_strlen($password) < 8) {
            $this->errors[] = 'Короткий пароль - длина пароля должна быть не менее 8 символов.';
            return false;
        }

        return true;
    }
}