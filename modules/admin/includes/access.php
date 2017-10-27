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

$config = $container->get('config')['setting'];

// Проверяем права доступа
if ($systemUser->rights < 7) {
    exit(_t('Access denied'));
}

echo '<div class="phdr"><a href="index.php"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('Permissions') . '</div>';

if (isset($_POST['submit'])) {
    $config['mod_reg'] = isset($_POST['reg']) ? intval($_POST['reg']) : 0;
    $config['mod_forum'] = isset($_POST['forum']) ? intval($_POST['forum']) : 0;
    $config['mod_guest'] = isset($_POST['guest']) ? intval($_POST['guest']) : 0;
    $config['mod_lib'] = isset($_POST['lib']) ? intval($_POST['lib']) : 0;
    $config['mod_lib_comm'] = isset($_POST['libcomm']);
    $config['mod_down'] = isset($_POST['down']) ? intval($_POST['down']) : 0;
    $config['mod_down_comm'] = isset($_POST['downcomm']);
    $config['active'] = isset($_POST['active']) ? intval($_POST['active']) : 0;
    $config['site_access'] = isset($_POST['access']) ? intval($_POST['access']) : 0;

    $configFile = "<?php\n\n" . 'return ' . var_export(['mobicms' => $config], true) . ";\n";

    if (!file_put_contents(ROOT_PATH . 'system/config/autoload/system.local.php', $configFile)) {
        echo 'ERROR: Can not write system.local.php</body></html>';
        exit;
    }

    echo '<div class="rmenu">' . _t('Settings are saved successfully') . '</div>';

    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
}

$color = ['red', 'yelow', 'green', 'gray'];
echo '<form method="post" action="index.php?act=access">';

// Управление доступом к Форуму
echo '<div class="menu"><p>' .
    '<h3>' . _t('Forum') . '</h3>' .
    '<div style="font-size: x-small">' .
    '<input type="radio" value="2" name="forum" ' . ($config['mod_forum'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . _t('Access is allowed') . '<br>' .
    '<input type="radio" value="1" name="forum" ' . ($config['mod_forum'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . _t('Only for authorized') . '<br>' .
    '<input type="radio" value="3" name="forum" ' . ($config['mod_forum'] == 3 ? 'checked="checked"' : '') . '/>&#160;' . _t('Read only') . '<br>' .
    '<input type="radio" value="0" name="forum" ' . (!$config['mod_forum'] ? 'checked="checked"' : '') . '/>&#160;' . _t('Access denied') .
    '</div></p>';

// Управление доступом к Гостевой
echo '<p><h3>' . _t('Guestbook') . '</h3>' .
    '<div style="font-size: x-small">' .
    '<input type="radio" value="2" name="guest" ' . ($config['mod_guest'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . _t('Access is allowed') . '<br>' .
    '<input type="radio" value="1" name="guest" ' . ($config['mod_guest'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . _t('Only for authorized') . '<br>' .
    '<input type="radio" value="0" name="guest" ' . (!$config['mod_guest'] ? 'checked="checked"' : '') . '/>&#160;' . _t('Access denied') .
    '</div></p>';

// Управление доступом к Библиотеке
echo '<p><h3>' . _t('Library') . '</h3>' .
    '<div style="font-size: x-small">' .
    '<input type="radio" value="2" name="lib" ' . ($config['mod_lib'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . _t('Access is allowed') . '<br>' .
    '<input type="radio" value="1" name="lib" ' . ($config['mod_lib'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . _t('Only for authorized') . '<br>' .
    '<input type="radio" value="0" name="lib" ' . (!$config['mod_lib'] ? 'checked="checked"' : '') . '/>&#160;' . _t('Access denied') . '<br>' .
    '<input name="libcomm" type="checkbox" value="1" ' . ($config['mod_lib_comm'] ? 'checked="checked"' : '') . ' />&#160;' . _t('Comments') .
    '</div></p>';

// Управление доступом к Загрузкам
echo '<p><h3>' . _t('Downloads') . '</h3>' .
    '<div style="font-size: x-small">' .
    '<input type="radio" value="2" name="down" ' . ($config['mod_down'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . _t('Access is allowed') . '<br>' .
    '<input type="radio" value="1" name="down" ' . ($config['mod_down'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . _t('Only for authorized') . '<br>' .
    '<input type="radio" value="0" name="down" ' . (!$config['mod_down'] ? 'checked="checked"' : '') . '/>&#160;' . _t('Access denied') . '<br>' .
    '<input name="downcomm" type="checkbox" value="1" ' . ($config['mod_down_comm'] ? 'checked="checked"' : '') . ' />&#160;' . _t('Comments') .
    '</div></p>';

// Управление доступом к Активу сайта (списки юзеров и т.д.)
echo '<p><h3>' . _t('Community') . '</h3>' .
    '<div style="font-size: x-small">' .
    '<input type="radio" value="1" name="active" ' . ($config['active'] ? 'checked="checked"' : '') . '/>&#160;' . _t('Access is allowed') . '<br>' .
    '<input type="radio" value="0" name="active" ' . (!$config['active'] ? 'checked="checked"' : '') . '/>&#160;' . _t('Only for authorized') . '<br>' .
    '</div></p></div>';

// Управление доступом к Регистрации
echo '<div class="gmenu"><h3>' . _t('Registration') . '</h3>' .
    '<div style="font-size: x-small">' .
    '<input type="radio" value="2" name="reg" ' . ($config['mod_reg'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . _t('Access is allowed') . '<br>' .
    '<input type="radio" value="1" name="reg" ' . ($config['mod_reg'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . _t('With moderation') . '<br>' .
    '<input type="radio" value="0" name="reg" ' . (!$config['mod_reg'] ? 'checked="checked"' : '') . '/>&#160;' . _t('Access denied') .
    '</div></div>';

echo '<div class="phdr"><small>' . _t('Administrators always have access to all closed modules and comments') . '</small></div>' .
    '<p><input type="submit" name="submit" id="button" value="' . _t('Save') . '" /></p>' .
    '<p><a href="index.php">' . _t('Admin Panel') . '</a></p></form>';
