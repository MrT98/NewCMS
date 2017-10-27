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

/** @var Mobicms\Checkpoint\UserConfig $userConfig */
$userConfig = $container->get(Mobicms\Api\UserInterface::class)->getConfig();

/** @var Mobicms\Api\ToolsInterface $tools */
$tools = $container->get(Mobicms\Api\ToolsInterface::class);

use Library\Hashtags;
use Library\Rating;

echo '<div class="phdr"><strong><a href="?">' . _t('Library') . '</a></strong> | ' . _t('New Articles') . '</div>';

$total = $db->query("SELECT COUNT(*) FROM `library_texts` WHERE `time` > '" . (time() - 259200) . "' AND `premod`=1")->fetchColumn();

if ($total) {
    $page = $page >= ceil($total / $userConfig->kmess) ? ceil($total / $userConfig->kmess) : $page;
    $start = $page == 1 ? 0 : ($page - 1) * $userConfig->kmess;

    if ($total > $userConfig->kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('?act=new&amp;', $total) . '</div>';
    }

    $sql = $db->prepare("SELECT libtxt.`id`, libtxt.`name`, libtxt.`time`, `uploader`, `uploader_id`, `count_views`, `comments`, `comm_count`, `cat_id`, `announce`, libcat.name AS catName
                    FROM `library_texts` libtxt
                    JOIN library_cats libcat ON libtxt.cat_id=libcat.id
                    WHERE libtxt.`time`>? AND `premod`=? ORDER BY libtxt.`time` DESC LIMIT " . $start . "," . $userConfig->kmess);
    $sql->execute([(time() - 259200), 1]);
    $i = 0;
    while ($row = $sql->fetch()) {
        echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">'
            . (file_exists('../uploads/library/images/small/' . $row['id'] . '.png')
                ? '<div class="avatar"><img src="../uploads/library/images/small/' . $row['id'] . '.png" alt="screen" /></div>'
                : '')
            . '<div class="righttable"><h4><a href="index.php?id=' . $row['id'] . '">' . $tools->checkout($row['name']) . '</a></h4>'
            . '<div><small>' . $tools->checkout($row['announce'], 0, 2) . '</small></div></div>';

        // Описание к статье
        $obj = new Hashtags($row['id']);
        $rate = new Rating($row['id']);
        echo '<table class="desc">'
            // Раздел
            . '<tr>'
            . '<td class="caption">' . _t('Section') . ':</td>'
            . '<td><a href="?do=dir&amp;id=' . $row['cat_id'] . '">' . $tools->checkout($row['catName']) . '</a></td>'
            . '</tr>'
            // Тэги
            . ($obj->getAllStatTags() ? '<tr><td class="caption">' . _t('Tags') . ':</td><td>' . $obj->getAllStatTags(1) . '</td></tr>' : '')
            // Кто добавил?
            . '<tr>'
            . '<td class="caption">' . _t('Who added') . ':</td>'
            . '<td><a href="' . $container->get('config')['setting']['homeurl'] . '/profile/?user=' . $row['uploader_id'] . '">' . $tools->checkout($row['uploader']) . '</a> (' . $tools->displayDate($row['time']) . ')</td>'
            . '</tr>'
            // Рейтинг
            . '<tr>'
            . '<td class="caption">' . _t('Rating') . ':</td>'
            . '<td>' . $rate->viewRate() . '</td>'
            . '</tr>'
            // Прочтений
            . '<tr>'
            . '<td class="caption">' . _t('Number of readings') . ':</td>'
            . '<td>' . $row['count_views'] . '</td>'
            . '</tr>'
            // Комментарии
            . '<tr>';
        if ($row['comments']) {
            echo '<td class="caption"><a href="?act=comments&amp;id=' . $row['id'] . '">' . _t('Comments') . '</a>:</td><td>' . $row['comm_count'] . '</td>';
        } else {
            echo '<td class="caption">' . _t('Comments') . ':</td><td>' . _t('Comments are closed') . '</td>';
        }
        echo '</tr></table>';

        echo '</div>';
    }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

if ($total > $userConfig->kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=new&amp;', $total) . '</div>';
}

echo '<p><a href="?">' . _t('To Library') . '</a></p>';
