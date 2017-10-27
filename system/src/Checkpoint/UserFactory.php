<?php
/**
 * mobiCMS (https://mobicms.org/)
 * This file is part of mobiCMS Content Management System.
 *
 * @license     https://opensource.org/licenses/GPL-3.0 GPL-3.0 (see the LICENSE.md file)
 * @link        http://mobicms.org mobiCMS Project
 * @copyright   Copyright (C) mobiCMS Community
 */

namespace Mobicms\Checkpoint;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

class UserFactory
{
    /**
     * @var \PDO
     */
    private $db;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    private $userData;

    public function __invoke(ContainerInterface $container)
    {
        $this->db       = $container->get(\PDO::class);
        $this->request  = $container->get(ServerRequestInterface::class);
        $this->userData = $this->authorize();

        return new User($this->userData);
    }

    /**
     * Авторизация пользователя и получение его данных из базы
     */
    protected function authorize()
    {
        $user_id   = false;
        $user_nick = false;

        if (isset($_SESSION['uid']) && isset($_SESSION['unick'])) {
            // Авторизация по сессии
            $user_id   = intval($_SESSION['uid']);
            $user_nick = $_SESSION['unick'];
        }

        if ($user_id && $user_nick) {
            $req = $this->db->query('SELECT * FROM `users` WHERE `id` = ' . $user_id);

            if ($req->rowCount()) {
                $userData = $req->fetch();
                $permit = $userData['failed_login'] < 3
                || $userData['failed_login'] > 2
                && $userData['ip'] == $this->request->getAttribute('ip')
                && $userData['browser'] == $this->request->getAttribute('user_agent')
                    ? true
                    : false;

                if ($permit && mb_strtolower($user_nick) === $userData['nickname']) {
                    // Проверяем на бан
                    $userData['ban'] = $this->banCheck($userData['id']);

                    // Если есть бан, обнуляем привилегии
                    if (!empty($userData['ban'])) {
                        $userData['rights'] = 0;
                    }

                    return $userData;
                } else {
                    // Если авторизация не прошла
                    $this->db->query("UPDATE `users` SET `failed_login` = '" . ($userData['failed_login'] + 1) . "' WHERE `id` = " . $userData['id']);
                    $this->userUnset();
                }
            } else {
                // Если пользователь не существует
                $this->userUnset();
            }
        }

        return $this->userTemplate();
    }

    /**
     * Проверка на бан
     *
     * @param int $userId
     * @return array
     */
    protected function banCheck($userId)
    {
        $ban = [];
        $req = $this->db->query("SELECT * FROM `cms_ban_users` WHERE `user_id` = " . $userId . " AND `ban_time` > '" . time() . "'");

        while ($res = $req->fetch()) {
            $ban[$res['ban_type']] = 1;
        }

        return $ban;
    }

    protected function userTemplate()
    {
        $template = [
            'id'            => 0,
            'nickname'      => '',
            'password'      => '',
            'name'          => '',
            'rights'        => 0,
            'failed_login'  => 0,
            'imname'        => '',
            'sex'           => '',
            'komm'          => 0,
            'postforum'     => 0,
            'postguest'     => 0,
            'yearofbirth'   => 0,
            'datereg'       => 0,
            'lastdate'      => 0,
            'mail'          => '',
            'icq'           => '',
            'skype'         => '',
            'jabber'        => '',
            'www'           => '',
            'about'         => '',
            'live'          => '',
            'mibile'        => '',
            'status'        => '',
            'ip'            => '',
            'ip_via_proxy'  => '',
            'browser'       => '',
            'preg'          => '',
            'regadm'        => '',
            'mailvis'       => '',
            'dayb'          => '',
            'monthb'        => '',
            'sestime'       => '',
            'total_on_site' => '',
            'lastpost'      => '',
            'rest_code'     => '',
            'rest_time'     => '',
            'place'         => '',
            'set_user'      => '',
            'set_forum'     => '',
            'set_mail'      => '',
            'comm_count'    => '',
            'comm_old'      => '',
            'smileys'       => '',
            'ban'           => [],
        ];

        return $template;
    }

    /**
     * Уничтожаем данные авторизации юзера
     */
    protected function userUnset()
    {
        unset($_SESSION['uid']);
        unset($_SESSION['unick']);
    }
}
