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

ob_start();

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Mobicms\Api\UserInterface $systemUser */
$systemUser = $container->get(Mobicms\Api\UserInterface::class);

/** @var Mobicms\Api\ToolsInterface $tools */
$tools = $container->get(Mobicms\Api\ToolsInterface::class);

// Удалить картинку
if ($img && $user['id'] == $systemUser->id || $systemUser->rights >= 6) {
    $req = $db->query("SELECT * FROM `cms_album_files` WHERE `id` = '$img' AND `user_id` = '" . $user['id'] . "' LIMIT 1");

    if ($req->rowCount()) {
        $res = $req->fetch();
        $album = $res['album_id'];
        echo '<div class="phdr"><a href="?act=show&amp;al=' . $album . '&amp;user=' . $user['id'] . '"><b>' . _t('Photo Album') . '</b></a> | ' . _t('Delete image') . '</div>';
        //TODO: Администрация не должна удалять фотки старших по должности
        if (isset($_POST['submit'])) {
            // Удаляем файлы картинок
            @unlink(UPLOAD_PATH . 'users/album/' . $user['id'] . '/' . $res['img_name']);
            @unlink(UPLOAD_PATH . 'users/album/' . $user['id'] . '/' . $res['tmb_name']);

            // Удаляем записи из таблиц
            $db->exec("DELETE FROM `cms_album_files` WHERE `id` = '$img'");
            $db->exec("DELETE FROM `cms_album_votes` WHERE `file_id` = '$img'");
            $db->exec("DELETE FROM `cms_album_comments` WHERE `sub_id` = '$img'");

            header('Location: ?act=show&al=' . $album . '&user=' . $user['id']);
        } else {
            echo '<div class="rmenu"><form action="?act=image_delete&amp;img=' . $img . '&amp;user=' . $user['id'] . '" method="post">' .
                '<p>' . _t('Are you sure you want to delete this image?') . '</p>' .
                '<p><input type="submit" name="submit" value="' . _t('Delete') . '"/></p>' .
                '</form></div>' .
                '<div class="phdr"><a href="?act=show&amp;al=' . $album . 'user=' . $user['id'] . '">' . _t('Cancel') . '</a></div>';
        }
    } else {
        echo $tools->displayError(_t('Wrong data'));
    }
}
