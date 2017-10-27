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

/** @var Mobicms\Checkpoint\UserConfig $userConfig */
$userConfig = $systemUser->getConfig();

/** @var Mobicms\Api\ToolsInterface $tools */
$tools = $container->get(Mobicms\Api\ToolsInterface::class);

/** @var League\Plates\Engine $view */
$view = $container->get(League\Plates\Engine::class);

if (!$al) {
    exit(_t('Wrong data'));
}

$req = $db->query("SELECT * FROM `cms_album_cat` WHERE `id` = '$al'");

if (!$req->rowCount()) {
    exit(_t('Wrong data'));
}

$album = $req->fetch();
$viewImg = isset($_GET['view']);

// Показываем выбранный альбом с фотографиями
echo '<div class="phdr"><a href="index.php"><b>' . _t('Photo Albums') . '</b></a> | <a href="?act=list&amp;user=' . $user['id'] . '">' . _t('Personal') . '</a></div>';

if ($user['id'] == $systemUser->id && empty($systemUser->ban) || $systemUser->rights >= 7) {
    echo '<div class="topmenu"><a href="?act=image_upload&amp;al=' . $al . '&amp;user=' . $user['id'] . '">' . _t('Add image') . '</a></div>';
}

echo '<div class="user"><p>' . $tools->displayUser($user) . '</p></div>' .
    '<div class="phdr">' . _t('Album') . ': ' .
    ($viewImg ? '<a href="?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '"><b>' . $tools->checkout($album['name']) . '</b></a>' : '<b>' . $tools->checkout($album['name']) . '</b>');

if (!empty($album['description'])) {
    echo '<div class="sub">' . $tools->checkout($album['description'], 1) . '</div>';
}

echo '</div>';

// Проверяем права доступа к альбому
if ($album['access'] != 2) {
    unset($_SESSION['ap']);
}

if (($album['access'] == 1 || $album['access'] == 3)
    && $user['id'] != $systemUser->id
    && $systemUser->rights < 7
) {
    // Доступ закрыт
    echo $view->render('system::app/legacy', [
        'title'   => _t('Album'),
        'content' => $tools->displayError(_t('Access forbidden'), '<a href="?act=list&amp;user=' . $user['id'] . '">' . _t('Album List') . '</a>'),
    ]);
    exit;
} elseif ($album['access'] == 2
    && $user['id'] != $systemUser->id
    && $systemUser->rights < 7
) {
    // Доступ через пароль
    if (isset($_POST['password'])) {
        if ($album['password'] == trim($_POST['password'])) {
            $_SESSION['ap'] = $album['password'];
        } else {
            echo $tools->displayError(_t('Incorrect Password'));
        }
    }

    if (!isset($_SESSION['ap']) || $_SESSION['ap'] != $album['password']) {
        echo '<form action="?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '" method="post"><div class="menu"><p>' .
            _t('You must type a password to view this album') . '<br>' .
            '<input type="text" name="password"/></p>' .
            '<p><input type="submit" name="submit" value="' . _t('Login') . '"/></p>' .
            '</div></form>' .
            '<div class="phdr"><a href="?act=list&amp;user=' . $user['id'] . '">' . _t('Album List') . '</a></div>';

        echo $view->render('system::app/legacy', [
            'title'   => _t('Album'),
            'content' => ob_get_clean(),
        ]);
        exit;
    }
}

// Просмотр альбома и фотографий
if ($viewImg) {
    $userConfig->offsetSet('kmess', 1);
    $page = isset($_REQUEST['page']) && $_REQUEST['page'] > 0 ? intval($_REQUEST['page']) : 1;
    $start = isset($_REQUEST['page']) ? $page - 1 : ($db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `album_id` = '$al' AND `id` > '$img'")->fetchColumn());

    // Обрабатываем ссылку для возврата
    if (empty($_SESSION['ref'])) {
        $_SESSION['ref'] = htmlspecialchars($_SERVER['HTTP_REFERER']);
    }
} else {
    unset($_SESSION['ref']);
    $start = $tools->getPgStart();
}

$total = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `album_id` = '$al'")->fetchColumn();

if ($total > $userConfig->kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '&amp;' . ($viewImg ? 'view&amp;' : ''), $total, $userConfig->kmess, $start) . '</div>';
}

if ($total) {
    $req = $db->query("SELECT * FROM `cms_album_files` WHERE `user_id` = '" . $user['id'] . "' AND `album_id` = '$al' ORDER BY `id` DESC LIMIT $start, " . $userConfig->kmess);
    $i = 0;

    while ($res = $req->fetch()) {
        echo($i % 2 ? '<div class="list2">' : '<div class="list1">');
        if ($viewImg) {
            // Предпросмотр отдельного изображения
            if ($user['id'] == $systemUser->id && isset($_GET['profile'])) {
                copy(
                    UPLOAD_PATH . 'users/album/' . $user['id'] . '/' . $res['tmb_name'],
                    UPLOAD_PATH . 'users/photo/' . $systemUser->id . '_small.jpg'
                );
                copy(
                    UPLOAD_PATH . 'users/album/' . $user['id'] . '/' . $res['img_name'],
                    UPLOAD_PATH . 'users/photo/' . $systemUser->id . '.jpg'
                );
                echo '<span class="green"><b>' . _t('Photo added to the profile') . '</b></span><br>';
            }
            echo '<a href="' . $_SESSION['ref'] . '"><img src="../assets/modules/album/image.php?u=' . $user['id'] . '&amp;f=' . $res['img_name'] . '" /></a>';

            // Счетчик просмотров
            if (!$db->query("SELECT COUNT(*) FROM `cms_album_views` WHERE `user_id` = '" . $systemUser->id . "' AND `file_id` = " . $res['id'])->fetchColumn()) {
                $db->exec("INSERT INTO `cms_album_views` SET `user_id` = '" . $systemUser->id . "', `file_id` = '" . $res['id'] . "', `time` = " . time());
                $views = $db->query("SELECT COUNT(*) FROM `cms_album_views` WHERE `file_id` = '" . $res['id'] . "'")->fetchColumn();
                $db->exec("UPDATE `cms_album_files` SET `views` = '$views' WHERE `id` = " . $res['id']);
            }
        } else {
            // Предпросмотр изображения в списке
            echo '<a href="?act=show&amp;al=' . $al . '&amp;img=' . $res['id'] . '&amp;user=' . $user['id'] . '&amp;view"><img src="../uploads/users/album/' . $user['id'] . '/' . $res['tmb_name'] . '" /></a>';
        }

        if (!empty($res['description'])) {
            echo '<div class="gray">' . $tools->smilies($tools->checkout($res['description'], 1)) . '</div>';
        }

        echo '<div class="sub">';

        if ($user['id'] == $systemUser->id || $systemUser->rights >= 6) {
            echo implode(' | ', [
                '<a href="?act=image_edit&amp;img=' . $res['id'] . '&amp;user=' . $user['id'] . '">' . _t('Edit') . '</a>',
                '<a href="?act=image_move&amp;img=' . $res['id'] . '&amp;user=' . $user['id'] . '">' . _t('Move') . '</a>',
                '<a href="?act=image_delete&amp;img=' . $res['id'] . '&amp;user=' . $user['id'] . '">' . _t('Delete') . '</a>',
            ]);

            if ($user['id'] == $systemUser->id && $viewImg) {
                echo ' | <a href="?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '&amp;view&amp;img=' . $res['id'] . '&amp;profile">' . _t('Add to Profile') . '</a>';
            }
        }

        echo vote_photo($res) .
            '<div class="gray">' . _t('Views') . ': ' . $res['views'] . ', ' . _t('Downloads') . ': ' . $res['downloads'] . '</div>' .
            '<div class="gray">' . _t('Date') . ': ' . $tools->displayDate($res['time']) . '</div>' .
            '<a href="?act=comments&amp;img=' . $res['id'] . '">' . _t('Comments') . '</a> (' . $res['comm_count'] . ')<br>' .
            '<a href="?act=image_download&amp;img=' . $res['id'] . '">' . _t('Download') . '</a>' .
            '</div></div>';
        ++$i;
    }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

if ($total > $userConfig->kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '&amp;' . ($viewImg ? 'view&amp;' : ''), $total, $userConfig->kmess, $start) . '</div>' .
        '<p><form action="?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . ($viewImg ? '&amp;view' : '') . '" method="post">' .
        '<input type="text" name="page" size="2"/>' .
        '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
        '</form></p>';
}

echo '<p><a href="?act=list&amp;user=' . $user['id'] . '">' . _t('Album List') . '</a></p>';
