--
-- ДЕМО данные Новостей
--
INSERT INTO `news` (`time`, `avt`, `name`, `text`, `kom`) VALUES
(1217062347, 'admin', 'Ресурс начал работу!', 'Добро пожаловать на сайт!\nМы надеемся, что Вам тут понравится и Вы будете нашим постоянным посетителем.', 8);

--
-- ДЕМО данные Форума
--
INSERT INTO `forum` (`id`, `refid`, `type`, `time`, `user_id`, `from`, `realid`, `ip`, `ip_via_proxy`, `soft`, `text`, `close`, `close_who`, `vip`, `edit`, `tedit`, `kedit`, `curators`) VALUES
(1, 0, 'f', 0, 0, '', 1, 0, 0, 'Свободное общение на любую тему', 'Общение', 0, '', 0, '', 0, 0, ''),
(2, 1, 'r', 0, 0, '', 2, 0, 0, '', 'О разном', 0, '', 0, '', 0, 0, ''),
(3, 1, 'r', 0, 0, '', 1, 0, 0, '', 'Знакомства', 0, '', 0, '', 0, 0, ''),
(4, 0, 'f', 0, 0, '', 2, 0, 0, '', 'Жизнь ресурса', 0, '', 0, '', 0, 0, ''),
(5, 4, 'r', 0, 0, '', 1, 0, 0, '', 'Новости', 0, '', 0, '', 0, 0, ''),
(6, 4, 'r', 0, 0, '', 2, 0, 0, '', 'Предложения и пожелания', 0, '', 0, '', 0, 0, ''),
(7, 4, 'r', 0, 0, '', 3, 0, 0, '', 'Разное', 0, '', 0, '', 0, 0, ''),
(8, 3, 't', 1260814062, 1, 'mobiCMS', 0, 0, 0, '', 'Привет всем!', 0, '', 0, '', 0, 0, ''),
(9, 8, 'm', 1260814062, 1, 'mobiCMS', 0, 1270, 0, 'mobiCMS User Agent', 'Мы рады приветствовать Вас на нашем сайте :)\nДавайте знакомиться!', 0, '', 0, '', 0, 0, '');

--
-- ДЕМО данные Гостевой
--
INSERT INTO `guest` (`adm`, `time`, `user_id`, `name`, `text`, `ip`, `browser`, `admin`, `otvet`, `otime`) VALUES
(1, 1217060516, 1, 'admin', 'Добро пожаловать в Админ Клуб!\nСюда имеют доступ ТОЛЬКО Модераторы и Администраторы.\nПростым пользователям доступ сюда закрыт.', 2130706433, 'Opera/9.51', '', '', 0),
(0, 1217060536, 1, 'admin', 'Добро пожаловать в Гостевую!', 2130706433, 'Opera/9.51', 'admin', 'Проверка ответа Администратора', 1217064021),
(0, 1217061125, 1, 'admin', 'Для зарегистрированных пользователей Гостевая поддерживает BBcode:\n[b]жирный[/b]\n[i]курсив[/i]\n[u]подчеркнутый[/u]\n[red]красный[/red]\n[green]зеленый[/green]\n[blue]синий[/blue]\n\nи ссылки:\nhttp://gazenwagen.com\n\nДля гостей, эти функции закрыты.', 2130706433, 'Opera/9.51', '', '', 0);
