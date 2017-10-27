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

$pageTitle = _t('Mail');
ob_start();

if ($id) {
    /** @var Psr\Container\ContainerInterface $container */
    $container = App::getContainer();

    /** @var PDO $db */
    $db = $container->get(PDO::class);

    /** @var Mobicms\Api\UserInterface $systemUser */
    $systemUser = $container->get(Mobicms\Api\UserInterface::class);

    if (isset($_POST['submit'])) {
        $req = $db->query('SELECT * FROM `cms_mail` WHERE ((`user_id` = ' . $id . ' AND `from_id` = ' . $systemUser->id . ') OR (`user_id` = ' . $systemUser->id . ' AND `from_id` = ' . $id . ')) AND `delete` != ' . $systemUser->id);

        while ($row = $req->fetch()) {
            //Удаляем сообщения
            if ($row['delete'] > 0 || ($row['read'] == 0 && $row['user_id'] == $systemUser->id)) {
                //Удаляем файлы
                if (!empty($row['file_name']) && file_exists(UPLOAD_PATH . 'mail/' . $row['file_name'])) {
                    @unlink(UPLOAD_PATH . 'mail/' . $row['file_name']);
                }

                $db->exec('DELETE FROM `cms_mail` WHERE `id` = ' . $row['id']);
            } else {
                $db->exec('UPDATE `cms_mail` SET `delete` = ' . $systemUser->id . ' WHERE `id` = ' . $row['id']);
            }
        }

        //Удаляем контакт
        $db->exec('DELETE FROM `cms_contact` WHERE `user_id` = ' . $systemUser->id . ' AND `from_id` = ' . $id . ' LIMIT 1');
        echo '<div class="gmenu"><p>' . _t('Contact deleted') . '</p><p><a href="index.php">' . _t('Continue') . '</a></p></div>';
    } else {
        echo '<div class="phdr"><b>' . _t('Delete') . '</b></div>
			<div class="rmenu">
			<form action="index.php?act=deluser&amp;id=' . $id . '" method="post">
			<p>' . _t('When you delete a contact is deleted and all correspondence with him.<br>Are you sure you want to delete?') . '</p>
			<p><input type="submit" name="submit" value="' . _t('Delete') . '"/></p>
			</form>
			</div>';
        echo '<div class="phdr"><a href="' . (isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : 'index.php') . '">' . _t('Back') . '</a></div>';
    }
} else {
    echo '<div class="rmenu"><p>' . _t('Contact for removal isn\'t chosen') . '</p></div>';
}
