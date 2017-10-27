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

/** @var League\Plates\Engine $view */
$view = $container->get(League\Plates\Engine::class);

if ($systemUser->rights == 4 || $systemUser->rights >= 6) {
    ob_start();

    if (!$id) {
        $load_cat = $files_path;
    } else {
        $req_down = $db->query("SELECT * FROM `download__category` WHERE `id` = '" . $id . "' LIMIT 1");
        $res_down = $req_down->fetch();

        if (!$req_down->rowCount() || !is_dir($res_down['dir'])) {
            echo _t('The directory does not exist') . '<a href="?">' . _t('Downloads') . '</a>';
            exit;
        }

        $load_cat = $res_down['dir'];
    }

    if (isset($_POST['submit'])) {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $rus_name = isset($_POST['rus_name']) ? trim($_POST['rus_name']) : '';
        $desc = isset($_POST['desc']) ? trim($_POST['desc']) : '';
        $user_down = isset($_POST['user_down']) ? 1 : 0;
        $format = $user_down && isset($_POST['format']) ? trim($_POST['format']) : false;
        $error = [];

        if (empty($name)) {
            $error[] = _t('The required fields are not filled');
        }

        if (preg_match("/[^0-9a-zA-Z\-\_]+/", $name)) {
            $error[] = _t('Invalid characters');
        }

        if ($systemUser->rights == 9 && $user_down) {
            foreach (explode(',', $format) as $value) {
                if (!in_array(trim($value), $defaultExt)) {
                    $error[] = _t('You can write only the following extensions') . ': ' . implode(', ', $defaultExt);
                    break;
                }
            }
        }

        if ($error) {
            echo $view->render('system::app/legacy', [
                'title'   => _t('Create Folder'),
                'content' => $tools->displayError($error, '<a href="?act=add_cat&amp;id=' . $id . '">' . _t('Repeat') . '</a>'),
            ]);
            exit;
        }

        if (empty($rus_name)) {
            $rus_name = $name;
        }

        $dir = false;
        $load_cat = $load_cat . '/' . $name;

        if (!is_dir($load_cat)) {
            $dir = mkdir($load_cat, 0777);
        }

        if ($dir == true) {
            chmod($load_cat, 0777);

            $stmt = $db->prepare("
                INSERT INTO `download__category`
                (`refid`, `dir`, `sort`, `name`, `desc`, `field`, `text`, `rus_name`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $id,
                $load_cat,
                time(),
                $name,
                $desc,
                $user_down,
                $format,
                $rus_name,
            ]);
            $cat_id = $db->lastInsertId();

            echo '<div class="phdr"><b>' . _t('Create Folder') . '</b></div>' .
                '<div class="list1"><p>' . _t('The Folder is created') . '<br><a href="?id=' . $cat_id . '">' . _t('Continue') . '</a></p></div>';
        } else {
            echo _t('Error creating categories') . '<a href="?act=add_cat&amp;id=' . $id . '">' . _t('Repeat') . '</a>';
            exit;
        }
    } else {
        echo '<div class="phdr"><b>' . _t('Create Folder') . '</b></div><div class="menu">' .
            '<form action="?act=folder_add&amp;id=' . $id . '" method="post">' .
            '<p>' . _t('Folder Name') . ' [A-Za-z0-9]:<br><input type="text" name="name"/></p>' .
            '<p>' . _t('Title to display') . '<br><input type="text" name="rus_name"/></p>' .
            '<p>' . _t('Description') . ' (max. 500)<br><textarea name="desc" cols="24" rows="4"></textarea></p>';

        if ($systemUser->rights == 9) {
            echo '<p><input type="checkbox" name="user_down" value="1" /> ' . _t('Allow users to upload files') . '</p>' .
                _t('Allowed extensions') . ':<br><input type="text" name="format"/>' .
                '<div class="sub">' . _t('You can write only the following extensions') . ':<br> ' . implode(', ', $defaultExt) . '</div>';
        }

        echo '<p><input type="submit" name="submit" value="' . _t('Create') . '"/></p></form></div>';
    }

    echo '<div class="phdr">';

    if ($id) {
        echo '<a href="?id=' . $id . '">' . _t('Back') . '</a> | ';
    }

    echo '<a href="?">' . _t('Back') . '</a></div>';

    echo $view->render('system::app/legacy', [
        'title'   => _t('Create Folder'),
        'content' => ob_get_clean(),
    ]);
}
