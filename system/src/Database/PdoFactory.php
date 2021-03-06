<?php
/**
 * mobiCMS (https://mobicms.org/)
 * This file is part of mobiCMS Content Management System.
 *
 * @license     https://opensource.org/licenses/GPL-3.0 GPL-3.0 (see the LICENSE.md file)
 * @link        http://mobicms.org mobiCMS Project
 * @copyright   Copyright (C) mobiCMS Community
 */

namespace Mobicms\Database;

use Psr\Container\ContainerInterface;

class PdoFactory
{
    /**
     * @param ContainerInterface $container
     * @return \PDO
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config')['pdo'] ?? [];

        $dbHost = isset($config['db_host']) ? $config['db_host'] : 'localhost';
        $dbUser = isset($config['db_user']) ? $config['db_user'] : 'root';
        $dbPass = isset($config['db_pass']) ? $config['db_pass'] : '';
        $dbName = isset($config['db_name']) ? $config['db_name'] : 'MrT';

        try {
            $pdo = new \PDO('mysql:host=' . $dbHost . ';dbname=' . $dbName, $dbUser, $dbPass,
                [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'",
                    \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,
                ]
            );
        } catch (\PDOException $e) {
            echo '<h2>MySQL ERROR: ' . $e->getCode() . '</h2>';

            switch ($e->getCode()) {
                case 1045:
                    exit('Access credentials (username or password) to a database are incorrect');

                case 1049:
                    exit('The name of a database is specified incorrectly');

                case 2002:
                    exit('Invalid database server');
            }

            exit;
        }

        return $pdo;
    }
}
