<?php
/**
 * mobiCMS (https://mobicms.org/)
 * This file is part of mobiCMS Content Management System.
 *
 * @license     https://opensource.org/licenses/GPL-3.0 GPL-3.0 (see the LICENSE.md file)
 * @link        http://mobicms.org mobiCMS Project
 * @copyright   Copyright (C) mobiCMS Community
 */

return [
    'dependencies' => [
        'factories' => [
            FastRoute\RouteCollector::class                => Mobicms\Http\RouteCollectorFactory::class,
            League\Plates\Engine::class                    => Mobicms\View\PlatesEngineFactory::class,
            Mobicms\Api\ConfigInterface::class             => Mobicms\Config\ConfigFactory::class,
            Mobicms\Asset\Manager::class                   => Mobicms\Asset\ManagerFactory::class,
            Mobicms\Api\ToolsInterface::class              => Mobicms\Tools\Utilites::class,
            Mobicms\Api\UserInterface::class               => Mobicms\Checkpoint\UserFactory::class,
            Psr\Http\Message\ServerRequestInterface::class => Mobicms\Http\ServerRequestFactory::class,
            PDO::class                                     => Mobicms\Database\PdoFactory::class,
            Zend\I18n\Translator\Translator::class         => Zend\I18n\Translator\TranslatorServiceFactory::class,

            // Deprecaded dependencies
            Mobicms\Api\BbcodeInterface::class             => Mobicms\Deprecated\Bbcode::class,
            'counters'                                     => Mobicms\Deprecated\Counters::class,
        ],

        'aliases' => [],
    ],
];
