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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoStyles\helpers;

use Jig\Utils\FsUtils;
use oat\oatbox\service\ServiceManager;
use oat\tao\helpers\Template;
use oat\taoStyles\controller\Main;
use oat\taoStyles\model\themes\InvalidFileException;

/**
 * Class Helper
 *
 * @package oat\taoStyles\helpers
 */
class StyleHelper
{

    /**
     * Get all available templates
     *
     * @param $type
     *
     * @return array
     */
    public static function getTemplates($type) {
        $templates = [];
        foreach(glob(static::getViewPath('templates/' . $type . '/*.tpl')) as $tpl) {
            $templates[FsUtils::removeFileExtension($tpl)] = Template::getTemplate($type . '/' . basename($tpl));
        }
        return $templates;
    }


    /**
     * Get all available templates
     *
     * @param $type
     * @param $themeId
     *
     * @return string
     * @throws InvalidFileException
     */
    public static function getEncodedStylesheet($type, $themeId) {
        $cssPath = static::getViewPath('css/themes/' . $type . '/' . $themeId . '/theme.css');
        if(!is_readable($cssPath)) {
            throw new InvalidFileException('File not found: ' . $cssPath);
        }
        return 'data:text/css;charset=utf-8;base64,' . base64_encode(file_get_contents($cssPath));
    }


    /**
     * Get a path within 'views'
     *
     * @param string $pathFragment
     *
     * @return string
     */
    public static function getViewPath($pathFragment='') {
        $serviceManager = ServiceManager::getServiceManager();
        return $serviceManager->get(\common_ext_ExtensionsManager::SERVICE_ID)
                              ->getExtensionById(Main::EXTENSION_NAME)
                              ->getConstant('DIR_VIEWS') . $pathFragment;
    }
}
