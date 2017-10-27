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

/** @var Mobicms\Api\UserInterface $systemUser */
$systemUser = $container->get(Mobicms\Api\UserInterface::class);

/** @var Mobicms\Checkpoint\UserConfig $userConfig */
$userConfig = $systemUser->getConfig();

$config = $container->get('config')['setting'];

// Проверяем права доступа
if ($systemUser->rights < 9) {
    exit(_t('Access denied'));
}

echo '<div class="phdr"><a href="index.php"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('System Settings') . '</div>';

if (isset($_POST['submit'])) {
    // Сохраняем настройки системы
    $config['skindef'] = isset($_POST['skindef']) ? trim($_POST['skindef']) : 'default';
    $config['email'] = isset($_POST['madm']) ? trim($_POST['madm']) : '@';
    $config['timeshift'] = isset($_POST['timeshift']) ? intval($_POST['timeshift']) : 0;
    $config['copyright'] = isset($_POST['copyright']) ? trim($_POST['copyright']) : 'mobiCMS';
    $config['homeurl'] = isset($_POST['homeurl']) ? preg_replace("#/$#", '', trim($_POST['homeurl'])) : '/';
    $config['flsz'] = isset($_POST['flsz']) ? intval($_POST['flsz']) : 0;
    $config['gzip'] = isset($_POST['gz']);
    $config['meta_key'] = isset($_POST['meta_key']) ? trim($_POST['meta_key']) : 'mobicms';
    $config['meta_desc'] = isset($_POST['meta_desc']) ? trim($_POST['meta_desc']) : 'mobicms';

    $configFile = "<?php\n\n" . 'return ' . var_export(['setting' => $config], true) . ";\n";

    if (!file_put_contents(ROOT_PATH . 'system/config/autoload/system.local.php', $configFile)) {
        echo 'ERROR: Can not write system.local.php</body></html>';
        exit;
    }

    echo '<div class="rmenu">' . _t('Settings are saved successfully') . '</div>';

    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
}

// Форма ввода параметров системы
echo '<form action="index.php?act=settings" method="post"><div class="menu">';

// Общие настройки
echo '<p>' .
    '<h3>' . _t('Common Settings') . '</h3>' .
    _t('Web site address without the slash at the end') . '<br>' . '<input type="text" name="homeurl" value="' . htmlentities($config['homeurl']) . '"/><br>' .
    _t('Site copyright') . '<br>' . '<input type="text" name="copyright" value="' . htmlentities($config['copyright'], ENT_QUOTES, 'UTF-8') . '"/><br>' .
    _t('Site Email') . '<br>' . '<input name="madm" maxlength="50" value="' . htmlentities($config['email']) . '"/><br>' .
    _t('Max. file size') . ' (kb):<br>' . '<input type="text" name="flsz" value="' . intval($config['flsz']) . '"/><br>' .
    '<input name="gz" type="checkbox" value="1" ' . ($config['gzip'] ? 'checked="checked"' : '') . ' />&#160;' . _t('Gzip compression') .
    '</p>';

// Настройка времени
echo '<p>' .
    '<h3>' . _t('Time shift') . '</h3>' .
    '<input type="text" name="timeshift" size="2" maxlength="3" value="' . $config['timeshift'] . '"/> (+-12)<br>' .
    '<span style="font-weight:bold; background-color:#C0FFC0">' . date("H:i", time() + $config['timeshift'] * 3600) . '</span> ' . _t('System Time') .
    '<br><span style="font-weight:bold; background-color:#FFC0C0">' . date("H:i") . '</span> ' . _t('Server Time') .
    '</p>';

// META тэги
echo '<p>' .
    '<h3>' . _t('META tags') . '</h3>' .
    '&#160;' . _t('Keywords') . '<br>&#160;<textarea rows="' . $userConfig->fieldHeight . '" name="meta_key">' . $config['meta_key'] . '</textarea><br>' .
    '&#160;' . _t('Description') . '<br>&#160;<textarea rows="' . $userConfig->fieldHeight . '" name="meta_desc">' . $config['meta_desc'] . '</textarea>' .
    '</p>';

echo '</select>' .
    '</p><br><p><input type="submit" name="submit" value="' . _t('Save') . '"/></p></div></form>' .
    '<div class="phdr">&#160;</div>' .
    '<p><a href="index.php">' . _t('Admin Panel') . '</a></p>';
