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

$id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Zend\I18n\Translator\Translator $translator */
$translator = $container->get(Zend\I18n\Translator\Translator::class);
$translator->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

/** @var League\Plates\Engine $view */
$view = $container->get(League\Plates\Engine::class);

ob_start();

// Обрабатываем ссылку для возврата
if (empty($_SESSION['ref'])) {
    /** @var Mobicms\Api\ConfigInterface $config */
    $config = $container->get(Mobicms\Api\ConfigInterface::class);
    $_SESSION['ref'] = isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : $config['homeurl'];
}

// Сколько смайлов разрешено выбрать пользователям?
$user_smileys = 20;

// Названия директорий со смайлами
function smiliesCat()
{
    return [
        'animals'       => _t('Animals'),
        'brawl_weapons' => _t('Brawl, Weapons'),
        'emotions'      => _t('Emotions'),
        'flowers'       => _t('Flowers'),
        'food_alcohol'  => _t('Food, Alcohol'),
        'gestures'      => _t('Gestures'),
        'holidays'      => _t('Holidays'),
        'love'          => _t('Love'),
        'misc'          => _t('Miscellaneous'),
        'music'         => _t('Music, Dancing'),
        'sports'        => _t('Sports'),
        'technology'    => _t('Technology'),
    ];
}

// Выбор действия
$array = [
    'admsmilies',
    'avatars',
    'forum',
    'my_smilies',
    'set_my_sm',
    'smilies',
    'tags',
    'usersmilies',
];

if ($act && ($key = array_search($act, $array)) !== false && file_exists(__DIR__ . '/includes/' . $array[$key] . '.php')) {
    require __DIR__ . '/includes/' . $array[$key] . '.php';
} else {
    // Главное меню FAQ
    echo '<div class="phdr"><b>' . _t('Information, FAQ') . '</b></div>' .
        '<div class="menu"><a href="?act=forum">' . _t('Forum rules') . '</a></div>' .
        '<div class="menu"><a href="?act=tags">' . _t('bbCode Tags') . '</a></div>' .
        '<div class="menu"><a href="?act=avatars">' . _t('Avatars') . '</a></div>' .
        '<div class="menu"><a href="?act=smilies">' . _t('Smilies') . '</a></div>' .
        '<div class="phdr"><a href="' . $_SESSION['ref'] . '">' . _t('Back') . '</a></div>';
}

echo $view->render('system::app/legacy', [
    'title'   => _t('Information, FAQ'),
    'content' => ob_get_clean(),
]);
