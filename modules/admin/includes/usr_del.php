<?php
/**
 * mobiCMS (https://mobicms.org/)
 * This file is part of mobiCMS Content Management System.
 *
 * @license     https://opensource.org/licenses/GPL-3.0 GPL-3.0 (see the LICENSE.md file)
 * @link        http://mobicms.org mobiCMS Project
 * @copyright   Copyright (C) mobiCMS Community
 */

defined('MOBICMS') or die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Mobicms\Api\UserInterface $systemUser */
$systemUser = $container->get(Mobicms\Api\UserInterface::class);

/** @var Mobicms\Api\ToolsInterface $tools */
$tools = $container->get(Mobicms\Api\ToolsInterface::class);

// Проверяем права доступа
if ($systemUser->rights < 9) {
    exit(_t('Access denied'));
}

$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

$user = false;
$error = false;

if ($id && $id != $systemUser->id) {
    // Получаем данные юзера
    $req = $db->query('SELECT * FROM `users` WHERE `id` = ' . $id);

    if ($req->rowCount()) {
        $user = $req->fetch();

        if ($user['rights'] > $systemUser->rights) {
            $error = _t('You cannot delete higher administration');
        }
    } else {
        $error = _t('User does not exists');
    }
} else {
    $error = _t('Wrong data');
}

if (!$error) {
    // Считаем комментарии в библиотеке
    $comm_lib = (int)$db->query("SELECT COUNT(*) FROM `cms_library_comments` WHERE `user_id` = '" . $user['id'] . "'")->fetchColumn();

    // Считаем комментарии к загрузкам
    $comm_dl = (int)$db->query("SELECT COUNT(*) FROM `download__comments` WHERE `user_id` = '" . $user['id'] . "'")->fetchColumn();

    // Считаем посты в личных гостевых
    $comm_gb = (int)$db->query("SELECT COUNT(*) FROM `cms_users_guestbook` WHERE `user_id` = '" . $user['id'] . "'")->fetchColumn();

    // Считаем комментарии в личных альбомах
    $comm_al = (int)$db->query("SELECT COUNT(*) FROM `cms_album_comments` WHERE `user_id` = '" . $user['id'] . "'")->fetchColumn();
    $comm_count = $comm_lib + $comm_dl + $comm_gb + $comm_al;

    // Считаем посты в Гостевой
    $guest_count = $db->query("SELECT COUNT(*) FROM `guest` WHERE `user_id` = '" . $user['id'] . "'")->fetchColumn();

    // Считаем созданные темы на Форуме
    $forumt_count = $db->query("SELECT COUNT(*) FROM `forum` WHERE `user_id` = '" . $user['id'] . "' AND `type` = 't' AND `close` != '1'")->fetchColumn();

    // Считаем посты на Форуме
    $forump_count = $db->query("SELECT COUNT(*) FROM `forum` WHERE `user_id` = '" . $user['id'] . "' AND `type` = 'm'  AND `close` != '1'")->fetchColumn();

    echo '<div class="phdr"><a href="index.php"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('Delete user') . '</div>';

    // Выводим краткие данные
    echo '<div class="user"><p>' . $tools->displayUser($user, [
            'lastvisit' => 1,
            'iphist'    => 1,
        ]) . '</p></div>';

    switch ($mod) {

        case 'del':
            // Удаляем личные данные
            $del = new Mobicms\Deprecated\CleanUser;
            $del->removeAlbum($user['id']);         // Удаляем личные Фотоальбомы
            $del->removeGuestbook($user['id']);     // Удаляем личную Гостевую
            $del->removeMail($user['id']);          // Удаляем почту

            if (isset($_POST['comments'])) {
                $del->cleanComments($user['id']);   // Удаляем комментарии
            }

            if (isset($_POST['forum'])) {
                $del->cleanForum($user['id']);      // Чистим Форум
            }

            $del->removeUser($user['id']);          // Удаляем пользователя

            // Оптимизируем таблицы
            $db->query("
                OPTIMIZE TABLE
                `cms_ban_users`,
                `guest`,
                `cms_album_comments`,
                `cms_users_guestbook`,
                `cms_album_votes`,
                `cms_album_views`,
                `cms_album_downloads`,
                `cms_album_cat`,
                `cms_album_files`,
                `cms_forum_rdm`
            ");

            echo '<div class="rmenu"><p><h3>' . _t('User deleted') . '</h3></p></div>';
            break;

        default:
            // Форма параметров удаления
            echo '<form action="index.php?act=usr_del&amp;mod=del&amp;id=' . $user['id'] . '" method="post"><div class="menu"><p><h3>' . _t('Cleaning activities') . '</h3>';

            if ($comm_count) {
                echo '<div><input type="checkbox" value="1" name="comments" checked="checked" />&#160;' . _t('Comments') . ' <span class="red">(' . $comm_count . ')</span></div>';
            }

            if ($forumt_count || $forump_count) {
                echo '<div><input type="checkbox" value="1" name="forum" checked="checked" />&#160;' . _t('Forum') . ' <span class="red">(' . $forumt_count . '&nbsp;/&nbsp;' . $forump_count . ')</span></div>';
                echo '<small><span class="gray">' . _t('All threads and posts created by the user go in the hidden state') . '</span></small>';
            }

            echo '</p></div><div class="rmenu"><p>' . _t('Are you sure that you want to delete this user?');
            echo '</p><p><input type="submit" value="' . _t('Delete') . '" name="submit" />';
            echo '</p></div></form>';
    }
} else {
    echo $tools->displayError($error);
}

echo '<p><a href="index.php">' . _t('Cancel') . '</a></p>';
