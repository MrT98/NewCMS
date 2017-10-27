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

/** @var Mobicms\Asset\Manager $asset */
$asset = $container->get(Mobicms\Asset\Manager::class);

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Mobicms\Api\UserInterface $systemUser */
$systemUser = $container->get(Mobicms\Api\UserInterface::class);

/** @var Mobicms\Api\ToolsInterface $tools */
$tools = $container->get(Mobicms\Api\ToolsInterface::class);

/** @var Mobicms\Api\ConfigInterface $config */
$config = $container->get(Mobicms\Api\ConfigInterface::class);

/** @var League\Plates\Engine $view */
$view = $container->get(League\Plates\Engine::class);

if ($systemUser->isValid())
{
    echo '<div class="menu"><h2><a href="' . $config->homeurl . '">' . _t('Home') . '</a></h2></div>';
} else {
    echo '<div class="phdr"><b>' . _t('Login') . '</b></div>';
    $error        = [];
    $captcha      = false;
    $display_form = 1;
    $user_login   = isset($_POST['n'])    ? trim($_POST['n']) : null;
    $user_pass    = isset($_POST['p'])    ? trim($_POST['p']) : null;
    $user_mem     = isset($_POST['mem'])  ? 1 : 0;
    $user_code    = isset($_POST['code']) ? trim($_POST['code']) : null;

    if (!$user_login || !$user_pass)
    {
        $error[] = _t('You have not entered login');
    } else if ((mb_strlen($user_login) < 2 || mb_strlen($user_login) > 20) || (mb_strlen($user_pass) < 6))
    {
        $error[] = _t('Invalid length');
    }

    if (!$error && $user_pass && $user_login)
    {
        // Запрос в базу на юзера
        $stmt = $db->prepare('SELECT * FROM `users` WHERE `nickname` = ? LIMIT 1');
        $stmt->execute([$user_login]);

        if ($stmt->rowCount()) {
            $systemUser = $stmt->fetch();

            if ($systemUser['failed_login'] > 2) {
                if ($user_code) {
                    if (mb_strlen($user_code) >= 3 && strtolower($user_code) == strtolower($_SESSION['code'])) {
                        // Если введен правильный проверочный код
                        unset($_SESSION['code']);
                        $captcha = true;
                    } else {
                        // Если проверочный код указан неверно
                        unset($_SESSION['code']);
                        $error[] = _t('The security code is not correct');
                    }
                } else {
                    // Показываем CAPTCHA
                    $display_form = 0;

                    $cap = new Mobicms\Captcha\Captcha;
                    $code = $cap->generateCode();
                    $_SESSION['code'] = $code;

                    echo '<form action="." method="post">' .
                        '<div class="menu"><p>' .
                        '<img alt="' . _t('Verification code') . '" width="' . $cap->width . '" height="' . $cap->height . '" src="' . $cap->generateImage($code) . '"/><br />' .
                        _t('Enter verification code') . ':<br>' .
                        '<input type="text" size="5" maxlength="5"  name="code"/>' .
                        '<input type="hidden" name="n" value="' . htmlspecialchars($user_login) . '"/>' .
                        '<input type="hidden" name="p" value="' . $user_pass . '"/>' .
                        '<input type="hidden" name="mem" value="' . $user_mem . '"/>' .
                        '<input type="submit" name="submit" value="' . _t('Continue') . '"/></p></div></form>';
                }
            }

            if ($systemUser['failed_login'] < 3 || $captcha) {
                if (md5(md5($user_pass)) === $systemUser['password']) {
                    // Если логин удачный
                    $display_form = 0;
                    $db->exec("UPDATE `users` SET `failed_login` = '0' WHERE `id` = " . $systemUser['id']);

                    if (!$systemUser['preg']) {
                        // Если регистрация не подтверждена
                        echo '<div class="rmenu"><p>' . _t('Sorry, but your request for registration is not considered yet. Please, be patient.') . '</p></div>';
                    } else {

                        // Установка данных сессии
                        $_SESSION['uid']   = $systemUser['id'];
                        $_SESSION['unick'] = $user_login;

                        $db->exec("UPDATE `users` SET `sestime` = '" . time() . "' WHERE `id` = " . $systemUser['id']);
                        $set_user = unserialize($systemUser['set_user']);
                        header('Location: ' . $config->homeurl);
                        exit;
                    }
                } else {
                    // Если логин неудачный
                    if ($systemUser['failed_login'] < 3) {
                        // Прибавляем к счетчику неудачных логинов
                        $db->exec("UPDATE `users` SET `failed_login` = '" . ($systemUser['failed_login'] + 1) . "' WHERE `id` = " . $systemUser['id']);
                    }

                    $error[] = _t('Authorization failed');
                }
            }
        } else {
            $error[] = _t('Authorization failed');
        }
    }

    if ($display_form) {
        if ($error) {
            echo $tools->displayError($error);
        }

        $info = '';

        echo $info;
        echo '<div class="gmenu"><form action="?" method="post"><p>' . _t('Username') . ':<br>' .
            '<input type="text" name="n" value="' . htmlentities($user_login, ENT_QUOTES, 'UTF-8') . '" maxlength="20"/>' .
            '<br>' . _t('Password') . ':<br>' .
            '<input type="password" name="p" maxlength="20"/></p>' .
            '<p><input type="checkbox" name="mem" value="1" checked="checked"/>' . _t('Remember') . '</p>' .
            '<p><input type="submit" value="' . _t('Login') . '"/></p>' .
            '</form></div>' .
            '<div class="menu"><p>' . $asset->img('user.png')->class('icon') . '<a href="../registration/">' . _t('Registration') . '</a></p></div>';
    }
}

echo $view->render('system::app/legacy', [
    'title'   => _t('Login'),
    'content' => ob_get_clean(),
]);
