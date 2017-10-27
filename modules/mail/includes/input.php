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
echo '<div class="phdr"><b>' . _t('Incoming messages') . '</b></div>';

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

/** @var Mobicms\Api\BbcodeInterface $bbcode */
$bbcode = $container->get(Mobicms\Api\BbcodeInterface::class);

$total = $db->query("
	SELECT COUNT(DISTINCT `cms_mail`.`user_id`)
	FROM `cms_mail`
	LEFT JOIN `cms_contact`
	ON `cms_mail`.`user_id`=`cms_contact`.`from_id`
	AND `cms_contact`.`user_id`='" . $systemUser->id . "'
	WHERE `cms_mail`.`from_id`='" . $systemUser->id . "'
	AND `cms_mail`.`sys`='0' AND `cms_mail`.`delete`!='" . $systemUser->id . "'
	AND `cms_contact`.`ban`!='1' AND `spam`='0'")->fetchColumn();

if ($total) {
    $req = $db->query("SELECT `users`.*, MAX(`cms_mail`.`time`) AS `time`
		FROM `cms_mail`
		LEFT JOIN `users` ON `cms_mail`.`user_id`=`users`.`id`
		LEFT JOIN `cms_contact` ON `cms_mail`.`user_id`=`cms_contact`.`from_id` AND `cms_contact`.`user_id`='" . $systemUser->id . "'
		WHERE `cms_mail`.`from_id`='" . $systemUser->id . "'
		AND `cms_mail`.`delete`!='" . $systemUser->id . "'
		AND `cms_mail`.`sys`='0'
		AND `cms_contact`.`ban`!='1'
		GROUP BY `cms_mail`.`user_id`
		ORDER BY MAX(`cms_mail`.`time`) DESC" . $tools->getPgStart(true));

    for ($i = 0; $row = $req->fetch(); ++$i) {
        $count_message = $db->query("SELECT COUNT(*) FROM `cms_mail`
            WHERE `user_id`='{$row['id']}'
            AND `from_id`='" . $systemUser->id . "'
            AND `delete`!='" . $systemUser->id . "'
            AND `sys`!='1'
        ")->fetchColumn();

        $last_msg = $db->query("SELECT *
            FROM `cms_mail`
            WHERE `from_id`='" . $systemUser->id . "'
            AND `user_id` = '{$row['id']}'
            AND `delete` != '" . $systemUser->id . "'
            ORDER BY `id` DESC
            LIMIT 1")->fetch();

        if (mb_strlen($last_msg['text']) > 500) {
            $text = mb_substr($last_msg['text'], 0, 500);
            $text = $tools->checkout($text, 1, 1);
            $text = $tools->smilies($text, $row['rights'] ? 1 : 0);
            $text = $bbcode->notags($text);
            $text .= '...<a href="index.php?act=write&amp;id=' . $row['id'] . '">' . _t('Continue') . ' &gt;&gt;</a>';
        } else {
            // Или, обрабатываем тэги и выводим весь текст
            $text = $tools->checkout($last_msg['text'], 1, 1);
            $text = $tools->smilies($text, $row['rights'] ? 1 : 0);
        }

        $arg = [
            'header' => '<span class="gray">(' . $tools->displayDate($last_msg['time']) . ')</span>',
            'body'   => '<div style="font-size: small">' . $text . '</div>',
            'sub'    => '<p><a href="index.php?act=write&amp;id=' . $row['id'] . '"><b>' . _t('Correspondence') . '</b></a> (' . $count_message . ') | <a href="index.php?act=ignor&amp;id=' . $row['id'] . '&amp;add">Игнор</a> | <a href="index.php?act=deluser&amp;id=' . $row['id'] . '">' . _t('Delete') . '</a></p>',
            'iphide' => 1,
        ];

        if (!$last_msg['read']) {
            echo '<div class="gmenu">';
        } else {
            echo $i % 2 ? '<div class="list1">' : '<div class="list2">';
        }

        echo $tools->displayUser($row, $arg);
        echo '</div>';
    }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

if ($total > $userConfig->kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=input&amp;', $total) . '</div>' .
        '<p><form action="index.php" method="get">
                <input type="hidden" name="act" value="input"/>
                <input type="text" name="page" size="2"/>
                <input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
}

echo '<p><a href="../profile/?act=office">' . _t('Personal') . '</a></p>';
