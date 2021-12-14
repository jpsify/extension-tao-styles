<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 */

use oat\taoStyles\scripts\install\RegisterPersistenceThemeService;

$extensionPath = dirname(__FILE__) . DIRECTORY_SEPARATOR;

return [
    'name' => 'taoStyles',
    'label' => 'TAO Look and Feel',
    'description' => 'Customize the appearance of the TAO platform',
    'license' => 'GPL-2.0',
    'author' => 'Open Assessment Technologies SA',
    'managementRole' => 'http://www.tao.lu/Ontologies/generis.rdf#taoStylesManager',
    'acl' => [
        [
            'grant', 'http://www.tao.lu/Ontologies/generis.rdf#taoStylesManager',
            [
                'ext'=>'taoStyles'
            ]
        ],
    ],
    'install' => [
        'php' => [
            RegisterPersistenceThemeService::class
        ],
    ],
    'uninstall' => [
    ],
    'update' => oat\taoStyles\scripts\update\Updater::class,
    'routes' => [
        '/taoStyles' => 'oat\\taoStyles\\controller'
    ],
    'constants' => [
        'DIR_VIEWS' => $extensionPath . 'views' . DIRECTORY_SEPARATOR
    ],
    'extra' => [
        'structures' => $extensionPath .'controller'. DIRECTORY_SEPARATOR . 'structures.xml',
    ]
];
