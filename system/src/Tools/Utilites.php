<?php
/**
 * mobiCMS (https://mobicms.org/)
 * This file is part of mobiCMS Content Management System.
 *
 * @license     https://opensource.org/licenses/GPL-3.0 GPL-3.0 (see the LICENSE.md file)
 * @link        http://mobicms.org mobiCMS Project
 * @copyright   Copyright (C) mobiCMS Community
 */

namespace Mobicms\Tools;

use Mobicms\Api\BbcodeInterface;
use Mobicms\Api\ConfigInterface;
use Mobicms\Api\ToolsInterface;
use Mobicms\Api\UserInterface;
use Mobicms\Asset\Manager as Asset;
use Mobicms\Checkpoint\UserConfig;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

class Utilites implements ToolsInterface
{
    /**
     * @var Asset
     */
    private $asset;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var \PDO
     */
    private $db;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var UserInterface::class
     */
    private $user;

    /**
     * @var UserConfig
     */
    private $userConfig;

    /**
     * @var ConfigInterface
     */
    private $config;

    public function __invoke(ContainerInterface $container)
    {
        $this->container = $container;
        $this->asset = $container->get(Asset::class);
        $this->config = $container->get(ConfigInterface::class);
        $this->db = $container->get(\PDO::class);
        $this->request = $container->get(ServerRequestInterface::class);
        $this->user = $container->get(UserInterface::class);
        $this->userConfig = $this->user->getConfig();

        return $this;
    }

    public function antiflood()
    {
        $config = $this->config['antiflood'];

        switch ($config['mode']) {
            // Адаптивный режим
            case 1:
                $adm = $this->db->query('SELECT COUNT(*) FROM `users` WHERE `rights` > 0 AND `lastdate` > ' . (time() - 300))->fetchColumn();
                $limit = $adm > 0 ? $config['day'] : $config['night'];
                break;
            // День
            case 3:
                $limit = $config['day'];
                break;
            // Ночь
            case 4:
                $limit = $config['night'];
                break;
            // По умолчанию день / ночь
            default:
                $c_time = date('G', time());
                $limit = $c_time > $config['day'] && $c_time < $config['night'] ? $config['day'] : $config['night'];
        }

        // Для Администрации задаем лимит в 4 секунды
        if ($this->user->rights > 0) {
            $limit = 4;
        }

        $flood = $this->user->lastpost + $limit - time();

        return $flood > 0 ? $flood : false;
    }

    /**
     * Обработка текстов перед выводом на экран
     *
     * @param string $str
     * @param int    $br   Параметр обработки переносов строк
     *                     0 - не обрабатывать (по умолчанию)
     *                     1 - обрабатывать
     *                     2 - вместо переносов строки вставляются пробелы
     * @param int    $tags Параметр обработки тэгов
     *                     0 - не обрабатывать (по умолчанию)
     *                     1 - обрабатывать
     *                     2 - вырезать тэги
     *
     * @return string
     */
    public function checkout($str, $br = 0, $tags = 0)
    {
        $str = htmlentities(trim($str), ENT_QUOTES, 'UTF-8');

        if ($br == 1) {
            $str = nl2br($str);
        } elseif ($br == 2) {
            $str = str_replace("\r\n", ' ', $str);
        }

        if ($tags == 1) {
            $str = $this->container->get(BbcodeInterface::class)->tags($str);
        } elseif ($tags == 2) {
            $str = $this->container->get(BbcodeInterface::class)->notags($str);
        }

        return trim($str);
    }

    /**
     * Показываем дату с учетом сдвига времени
     *
     * @param int $var Время в Unix формате
     * @return string Отформатированное время
     */
    public function displayDate($var)
    {
        $shift = ($this->config->timeshift + $this->userConfig->timeshift) * 3600;

        if (date('Y', $var) == date('Y', time())) {
            if (date('z', $var + $shift) == date('z', time() + $shift)) {
                return _t('Today', 'system') . ', ' . date("H:i", $var + $shift);
            }
            if (date('z', $var + $shift) == date('z', time() + $shift) - 1) {
                return _t('Yesterday', 'system') . ', ' . date("H:i", $var + $shift);
            }
        }

        return date("d.m.Y / H:i", $var + $shift);
    }

    /**
     * Сообщения об ошибках
     *
     * @param string|array $error Сообщение об ошибке (или массив с сообщениями)
     * @param string       $link  Необязательная ссылка перехода
     * @return string
     */
    public function displayError($error = '', $link = '')
    {
        return '<div class="rmenu"><p><b>' . _t('ERROR', 'system') . '!</b><br>'
            . (is_array($error) ? implode('<br>', $error) : $error) . '</p>'
            . (!empty($link) ? '<p>' . $link . '</p>' : '') . '</div>';
    }

    /**
     * Постраничная навигация
     * За основу взята доработанная функция от форума SMF 2.x.x
     *
     * @param string $url
     * @param int    $total
     * @param int    $listSize
     * @param int    $offset
     * @return string
     */
    public function displayPagination($url, $total, $listSize = null, $offset = null)
    {
        if ($offset === null) {
            $offset = $this->getPgStart();
        }

        if ($listSize === null) {
            $listSize = $this->userConfig->kmess;
        }

        $neighbors = 2;
        if ($offset >= $total) {
            $offset = max(0, $total - (($total % $listSize) == 0 ? $listSize : ($total % $listSize)));
        } else {
            $offset = max(0, (int)$offset - ((int)$offset % (int)$listSize));
        }

        $base_link = '<a class="pagenav" href="' . strtr($url, ['%' => '%%']) . 'page=%d' . '">%s</a>';
        $out[] = $offset == 0 ? '' : sprintf($base_link, $offset / $listSize, '&lt;&lt;');

        if ($offset > $listSize * $neighbors) {
            $out[] = sprintf($base_link, 1, '1');
        }

        if ($offset > $listSize * ($neighbors + 1)) {
            $out[] = '<span style="font-weight: bold;">...</span>';
        }

        for ($nCont = $neighbors; $nCont >= 1; $nCont--) {
            if ($offset >= $listSize * $nCont) {
                $tmpStart = $offset - $listSize * $nCont;
                $out[] = sprintf($base_link, $tmpStart / $listSize + 1, $tmpStart / $listSize + 1);
            }
        }

        $out[] = '<span class="currentpage"><b>' . ($offset / $listSize + 1) . '</b></span>';
        $tmpMaxPages = (int)(($total - 1) / $listSize) * $listSize;

        for ($nCont = 1; $nCont <= $neighbors; $nCont++) {
            if ($offset + $listSize * $nCont <= $tmpMaxPages) {
                $tmpStart = $offset + $listSize * $nCont;
                $out[] = sprintf($base_link, $tmpStart / $listSize + 1, $tmpStart / $listSize + 1);
            }
        }

        if ($offset + $listSize * ($neighbors + 1) < $tmpMaxPages) {
            $out[] = '<span style="font-weight: bold;">...</span>';
        }

        if ($offset + $listSize * $neighbors < $tmpMaxPages) {
            $out[] = sprintf($base_link, $tmpMaxPages / $listSize + 1, $tmpMaxPages / $listSize + 1);
        }

        if ($offset + $listSize < $total) {
            $display_page = ($offset + $listSize) > $total ? $total : ($offset / $listSize + 2);
            $out[] = sprintf($base_link, $display_page, '&gt;&gt;');
        }

        return implode(' ', $out);
    }

    public function displayPlace($place, $userId = 0)
    {
        $place = rtrim($place, '/');

        if (empty($place)) {
            $place = '/';
        }

        $part = explode('?', $place);

        $placelist = [
            '/'                 => '<a href="#home#/">' . _t('On the Homepage', 'system') . '</a>',
            '/users?act=online' => '<a href="#home#/users/?act=online">' . _t('Who is online?', 'system') . '</a>',
            '/news'             => '<a href="#home#/news/">' . _t('Reading the news', 'system') . '</a>',
            '/users'            => '<a href="#home#/users/">' . _t('List of Users', 'system') . '</a>',
            '/album'            => '<a href="#home#/album/">' . _t('Watching the photo album', 'system') . '</a>',
            '/downloads'        => '<a href="#home#/downloads/">' . _t('Downloads', 'system') . '</a>',
            '/help'             => '<a href="#home#/help/">' . _t('Reading the FAQ', 'system') . '</a>',
            '/forum'            => '<a href="#home#/forum/">' . _t('Forum', 'system') . '</a>&#160;/&#160;<a href="#home#/forum/?act=who">&gt;&gt;</a>',
            '/guestbook'        => '<a href="#home#/guestbook/">' . _t('Guestbook', 'system') . '</a>',
            '/library'          => '<a href="#home#/library/">' . _t('Library', 'system') . '</a>',
            '/redirect'         => _t('Redirect on external link to another site', 'system'),
            '/mail'             => _t('Personal correspondence', 'system'),
            '/profile'          => _t('Profile', 'system'),
            '/registration'     => _t('Registered on the site', 'system'),
        ];

        if (array_key_exists($place, $placelist)) {
            return str_replace('#home#', $this->config->homeurl, $placelist[$place]);
        } elseif (array_key_exists($part[0], $placelist)) {
            return str_replace('#home#', $this->config->homeurl, $placelist[$part[0]]);
        }

        return '<a href="' . $this->config->homeurl . '/">'
            . ($this->user->rights >= 6 ? '[' . ($place) . ']' : _t('Somewhere on the site', 'system'))
            . '</a>';
    }

    /**
     * Отображения личных данных пользователя
     *
     * @param int   $user Массив запроса в таблицу `users`
     * @param array $arg  Массив параметров отображения
     *                    [lastvisit] (boolean)   Дата и время последнего визита
     *                    [stshide]   (boolean)   Скрыть статус (если есть)
     *                    [iphide]    (boolean)   Скрыть (не показывать) IP и UserAgent
     *                    [iphist]    (boolean)   Показывать ссылку на историю IP
     *
     *                    [header]    (string)    Текст в строке после Ника пользователя
     *                    [body]      (string)    Основной текст, под ником пользователя
     *                    [sub]       (string)    Строка выводится вверху области "sub"
     *                    [footer]    (string)    Строка выводится внизу области "sub"
     *
     * @return string
     */
    public function displayUser($user = 0, array $arg = [])
    {
        global $mod;
        $out = false;
        $homeurl = $this->config['homeurl'];

        if (!$user['id']) {
            $out = '<b>' . _t('Guest', 'system') . '</b>';

            if (!empty($user['name'])) {
                $out .= ': ' . $user['name'];
            }

            if (!empty($arg['header'])) {
                $out .= ' ' . $arg['header'];
            }
        } else {
            $out .= '<table cellpadding="0" cellspacing="0"><tr><td>';

            if (file_exists(UPLOAD_PATH . 'users/avatar/' . $user['id'] . '.png')) {
                $out .= '<img src="' . $homeurl . '/uploads/users/avatar/' . $user['id'] . '.png" width="32" height="32" alt="" />&#160;';
            } else {
                $out .= '<img src="' . $this->asset->getAsset('images/empty.png') . '" width="32" height="32" alt="" />&#160;';
            }

            $out .= '</td><td>';

            if ($user['sex']) {
                $out .= $this->asset->img(($user['sex'] == 'm' ? 'm' : 'w') . ($user['datereg'] > time() - 86400 ? '_new' : '') . '.png')->class('icon-inline');
            } else {
                $out .= $this->asset->img('del.png')->class('icon');
            }

            $out .= !$this->user->isValid() || $this->user->id == $user['id'] ? '<b>' . $user['name'] . '</b>' : '<a href="' . $homeurl . '/profile/?user=' . $user['id'] . '"><b>' . $user['name'] . '</b></a>';
            $rank = [
                0 => '',
                1 => '(GMod)',
                2 => '(CMod)',
                3 => '(FMod)',
                4 => '(DMod)',
                5 => '(LMod)',
                6 => '(Smd)',
                7 => '(Adm)',
                9 => '(SV!)',
            ];
            $rights = isset($user['rights']) ? $user['rights'] : 0;
            $out .= ' ' . $rank[$rights];
            $out .= (time() > $user['lastdate'] + 300 ? '<span class="red"> [Off]</span>' : '<span class="green"> [ON]</span>');

            if (!empty($arg['header'])) {
                $out .= ' ' . $arg['header'];
            }

            if (!isset($arg['stshide']) && !empty($user['status'])) {
                $out .= '<div class="status">' . $this->asset->img('label.png')->class('icon-inline') . $user['status'] . '</div>';
            }

            $out .= '</td></tr></table>';
        }

        if (isset($arg['body'])) {
            $out .= '<div>' . $arg['body'] . '</div>';
        }

        $ipinf = isset($arg['iphide']) ? !$arg['iphide'] : ($this->user->rights ? 1 : 0);
        $lastvisit = time() > $user['lastdate'] + 300 && isset($arg['lastvisit']) ? $this->displayDate($user['lastdate']) : false;

        if ($ipinf || $lastvisit || isset($arg['sub']) && !empty($arg['sub']) || isset($arg['footer'])) {
            $out .= '<div class="sub">';

            if (isset($arg['sub'])) {
                $out .= '<div>' . $arg['sub'] . '</div>';
            }

            if ($lastvisit) {
                $out .= '<div><span class="gray">' . _t('Last Visit', 'system') . ':</span> ' . $lastvisit . '</div>';
            }

            if ($ipinf) {
                $out .= '<div><span class="gray">' . _t('Browser',
                        'system') . ':</span> ' . htmlspecialchars($user['browser']) . '</div>' .
                    '<div><span class="gray">' . _t('IP address', 'system') . ':</span> ';
                $ip = $user['ip'];

                if ($this->user->rights && isset($user['ip_via_proxy']) && $user['ip_via_proxy']) {
                    $out .= '<b class="red"><a href="' . $homeurl . '/admin/index.php?act=search_ip&amp;ip=' . $ip . '">' . $ip . '</a></b>';
                    $out .= '&#160;[<a href="' . $homeurl . '/admin/index.php?act=ip_whois&amp;ip=' . $ip . '">?</a>]';
                    $out .= ' / ';
                    $out .= '<a href="' . $homeurl . '/admin/index.php?act=search_ip&amp;ip=' . $user['ip_via_proxy'] . '">' . $user['ip_via_proxy'] . '</a>';
                    $out .= '&#160;[<a href="' . $homeurl . '/admin/index.php?act=ip_whois&amp;ip=' . $user['ip_via_proxy'] . '">?</a>]';
                } elseif ($this->user->rights) {
                    $out .= '<a href="' . $homeurl . '/admin/index.php?act=search_ip&amp;ip=' . $ip . '">' . $ip . '</a>';
                    $out .= '&#160;[<a href="' . $homeurl . '/admin/index.php?act=ip_whois&amp;ip=' . $ip . '">?</a>]';
                } else {
                    $out .= $ip;
                }

                $out .= '</div>';
            }

            if (isset($arg['footer'])) {
                $out .= $arg['footer'];
            }
            $out .= '</div>';
        }

        return $out;
    }

    /**
     * Получение флага для выбранной локали
     *
     * @param string $locale
     * @return string
     */
    public function getFlag($locale)
    {
        $file = ROOT_PATH . 'system/language' . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . 'lng.png';
        $flag = is_file($file) ? 'data:image/png;base64,' . base64_encode(file_get_contents($file)) : false;

        return $flag !== false ? '<img src="' . $flag . '" style="margin-right: 8px; vertical-align: middle">' : '';
    }

    /**
     * Get Pagination START offset
     *
     * @return int|string
     */
    public function getPgStart($db = false)
    {
        $queryParams = $this->request->getQueryParams();
        $page = $queryParams['page'] ?? 1;
        $start = isset($queryParams['page'])
            ? $page * $this->userConfig->kmess - $this->userConfig->kmess
            : $queryParams['start'] ?? 0;
        $start = abs(intval($start));

        return $db
            ? ' LIMIT ' . $this->userConfig->kmess . ' OFFSET ' . $start
            : $start;
    }

    /**
     * @return string
     */
    public function getSkin()
    {
        return $this->user->isValid() && !empty($this->userConfig->skin)
            ? $this->userConfig->skin
            : $this->config->skindef;
    }

    /**
     * Получаем данные пользователя
     *
     * @param int $id Идентификатор пользователя
     * @return array|bool
     */
    public function getUser($id = 0)
    {
        if ($id && $id != $this->user->id) {
            $req = $this->db->query("SELECT * FROM `users` WHERE `id` = '$id'");

            if ($req->rowCount()) {
                return $req->fetch();
            } else {
                return false;
            }
        } else {
            return $this->user;
        }
    }

    /**
     * Проверка на игнор у получателя
     *
     * @param $id
     * @return bool
     */
    public function isIgnor($id)
    {
        static $user_id = null;
        static $return = false;

        if (!$this->user->isValid() && !$id) {
            return false;
        }

        if (is_null($user_id) || $id != $user_id) {
            $user_id = $id;
            $req = $this->db->query("SELECT * FROM `cms_contact` WHERE `user_id` = '$id' AND `from_id` = " . $this->user->id);

            if ($req->rowCount()) {
                $res = $req->fetch();
                if ($res['ban'] == 1) {
                    $return = true;
                }
            }
        }

        return $return;
    }

    /**
     * Транслитерация с Русского в латиницу
     *
     * @param string $str
     * @return string
     */
    public function rusLat($str)
    {
        $replace = [
            'а' => 'a',
            'б' => 'b',
            'в' => 'v',
            'г' => 'g',
            'д' => 'd',
            'е' => 'e',
            'ё' => 'e',
            'ж' => 'j',
            'з' => 'z',
            'и' => 'i',
            'й' => 'i',
            'к' => 'k',
            'л' => 'l',
            'м' => 'm',
            'н' => 'n',
            'о' => 'o',
            'п' => 'p',
            'р' => 'r',
            'с' => 's',
            'т' => 't',
            'у' => 'u',
            'ф' => 'f',
            'х' => 'h',
            'ц' => 'c',
            'ч' => 'ch',
            'ш' => 'sh',
            'щ' => 'sch',
            'ъ' => "",
            'ы' => 'y',
            'ь' => "",
            'э' => 'ye',
            'ю' => 'yu',
            'я' => 'ya',
        ];

        return strtr(mb_strtolower($str), $replace);
    }

    /**
     * Обработка смайлов
     *
     * @param string $str
     * @param bool   $adm
     * @return string
     */
    public function smilies($str, $adm = false)
    {
        static $smiliesCache = [];

        if (empty($smiliesCache)) {
            $file = CACHE_PATH . 'smilies.cache';

            if (file_exists($file) && ($smileys = file_get_contents($file)) !== false) {
                $smiliesCache = unserialize($smileys);

                return strtr($str,
                    ($adm ? array_merge($smiliesCache['usr'], $smiliesCache['adm']) : $smiliesCache['usr']));
            } else {
                return $str;
            }
        } else {
            return strtr($str, ($adm ? array_merge($smiliesCache['usr'], $smiliesCache['adm']) : $smiliesCache['usr']));
        }
    }

    /**
     * Функция пересчета на дни, или часы
     *
     * @param int $var
     * @return bool|string
     */
    public function timecount($var)
    {
        if ($var < 0) {
            $var = 0;
        }

        $day = ceil($var / 86400);

        return $var >= 86400
            ? $day . ' ' . _p('Day', 'Days', $day, 'system')
            : date("G:i:s", mktime(0, 0, $var));
    }

    // Транслитерация текста
    public function trans($str)
    {
        $replace = [
            'a'  => 'а',
            'b'  => 'б',
            'v'  => 'в',
            'g'  => 'г',
            'd'  => 'д',
            'e'  => 'е',
            'yo' => 'ё',
            'zh' => 'ж',
            'z'  => 'з',
            'i'  => 'и',
            'j'  => 'й',
            'k'  => 'к',
            'l'  => 'л',
            'm'  => 'м',
            'n'  => 'н',
            'o'  => 'о',
            'p'  => 'п',
            'r'  => 'р',
            's'  => 'с',
            't'  => 'т',
            'u'  => 'у',
            'f'  => 'ф',
            'h'  => 'х',
            'c'  => 'ц',
            'ch' => 'ч',
            'w'  => 'ш',
            'sh' => 'щ',
            'q'  => 'ъ',
            'y'  => 'ы',
            'x'  => 'э',
            'yu' => 'ю',
            'ya' => 'я',
            'A'  => 'А',
            'B'  => 'Б',
            'V'  => 'В',
            'G'  => 'Г',
            'D'  => 'Д',
            'E'  => 'Е',
            'YO' => 'Ё',
            'ZH' => 'Ж',
            'Z'  => 'З',
            'I'  => 'И',
            'J'  => 'Й',
            'K'  => 'К',
            'L'  => 'Л',
            'M'  => 'М',
            'N'  => 'Н',
            'O'  => 'О',
            'P'  => 'П',
            'R'  => 'Р',
            'S'  => 'С',
            'T'  => 'Т',
            'U'  => 'У',
            'F'  => 'Ф',
            'H'  => 'Х',
            'C'  => 'Ц',
            'CH' => 'Ч',
            'W'  => 'Ш',
            'SH' => 'Щ',
            'Q'  => 'Ъ',
            'Y'  => 'Ы',
            'X'  => 'Э',
            'YU' => 'Ю',
            'YA' => 'Я',
        ];

        return strtr($str, $replace);
    }
}
