<?php
/**
 * mobiCMS (https://mobicms.org/)
 * This file is part of mobiCMS Content Management System.
 *
 * @license     https://opensource.org/licenses/GPL-3.0 GPL-3.0 (see the LICENSE.md file)
 * @link        http://mobicms.org mobiCMS Project
 * @copyright   Copyright (C) mobiCMS Community
 */

namespace Mobicms\Deprecated;

use Mobicms\Api\ToolsInterface;

/**
 * @deprecated
 */
class NewsWidget
{
    public $news;         // Текст новостей
    public $newscount;    // Общее к-во новостей
    public $lastnewsdate; // Дата последней новости
    private $settings = [];

    /**
     * @var \PDO
     */
    private $db;

    /**
     * @var ToolsInterface
     */
    private $tools;

    public function __construct()
    {
        /** @var \Psr\Container\ContainerInterface $container */
        $container = \App::getContainer();

        $this->db = $container->get(\PDO::class);
        $this->tools = $container->get(ToolsInterface::class);
        $this->settings = $container->get('config')['setting']['news'];
        $this->newscount = $this->newscount() . $this->lastnewscount();
        $this->news = $this->news();
    }

    // Запрос свежих новостей на Главную
    private function news()
    {
        if ($this->settings['view'] > 0) {
            $reqtime = $this->settings['days'] ? time() - ($this->settings['days'] * 86400) : 0;
            $req = $this->db->query("SELECT * FROM `news` WHERE `time` > '$reqtime' ORDER BY `time` DESC LIMIT " . $this->settings['quantity']);

            if ($req->rowCount()) {
                $i = 0;
                $news = '';

                while ($res = $req->fetch()) {
                    $text = $res['text'];
                    $moreLink = '';

                    // Если текст больше заданного предела, обрезаем
                    if (mb_strlen($text) > $this->settings['size']) {
                        $text = mb_substr($text, 0, $this->settings['size']);
                        $text = htmlentities($text, ENT_QUOTES, 'UTF-8');
                        $moreLink = ' &gt;&gt; <a href="news/index.php">' . _t('show more', 'system') . '...</a>';
                    }

                    $text = $this->tools->checkout(
                        $text,
                        $this->settings['breaks'] ? 1 : 2,
                        $this->settings['tags'] ? 1 : 2
                    );

                    if ($this->settings['smileys']) {
                        $text = $this->tools->smilies($text);
                    }

                    $text = $text . $moreLink;

                    // Определяем режим просмотра заголовка - текста
                    $news .= '<div class="news">';
                    switch ($this->settings['view']) {
                        case 2:
                            $news .= '<a href="news/index.php">' . $res['name'] . '</a>';
                            break;

                        case 3:
                            $news .= $text;
                            break;
                        default :
                            $news .= '<h2>' . $res['name'] . '</h2>' . $text;
                    }

                    // Ссылка на каменты
                    if (!empty($res['kom']) && $this->settings['view'] != 2 && $this->settings['kom'] == 1) {
                        $mes = $this->db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `refid` = '" . $res['kom'] . "'")->fetchColumn();
                        $komm = $mes - 1;

                        if ($komm >= 0) {
                            $news .= '<div style="margin: .4em 0"><a href="forum/?id=' . $res['kom'] . '">' . _t('Discuss', 'system') . '</a> (' . $komm . ')</div>';
                        }
                    }
                    $news .= '</div>';
                    ++$i;
                }

                return $news;
            }
        }

        return false;
    }

    /**
     * Счетчик всех новостей
     *
     * @return string
     */
    private function newscount()
    {
        $count = $this->db->query("SELECT COUNT(*) FROM `news`")->fetchColumn();

        return ($count ? $count : '0');
    }

    /**
     * Счетчик свежих новостей
     *
     * @return bool|string
     */
    private function lastnewscount()
    {
        $count = $this->db->query("SELECT COUNT(*) FROM `news` WHERE `time` > '" . (time() - 259200) . "'")->fetchColumn();

        return ($count > 0 ? '/<span class="red">+' . $count . '</span>' : false);
    }
}
