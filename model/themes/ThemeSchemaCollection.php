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


namespace oat\taoStyles\model\themes;

use oat\tao\helpers\Template;
use oat\taoStyles\model\themes\InvalidThemeSchemaException;
use Jig\Utils\StringUtils;

class ThemeSchemaCollection
{
    private $themeSchemata = [];

    private $ids = [];

    private $type = '';


    /**
     * ThemeConfiguration constructor.
     *
     * @param bool $ignoreDeactivated
     * @param string $type platform|items
     */
    public function __construct($type, $ignoreDeactivated = true) {
        $this->type = $type;
        $this->loadThemeSchemata($ignoreDeactivated);
    }


    /**
     * Load theme configuration from JSON file, if there is any
     *
     * @return bool
     */
    protected function loadThemeSchemata() {

        $path = dirname(dirname(__DIR__)) . '/themes/config/' . $this->type . '/theme-config.json';
        if(!is_readable($path)) {
            return false;
        }
        $rawConfig = json_decode(file_get_contents($path), true);
        if(empty($rawConfig)) {
            return false;
        }
        foreach($rawConfig as $key => $themeSchema) {
            $themeSchema = $this->addMissingProperties($themeSchema);
            if(!empty($themeSchema['active'])) {
                $this->themeSchemata[$key] = $themeSchema;
            }
        }
        return true;
    }


    /**
     * Make sure theme has all required fields
     *
     * @param array $themeSchema
     *
     * @return array
     * @throws \oat\taoStyles\model\themes\InvalidThemeSchemaException
     */
    protected function addMissingProperties(array $themeSchema) {
        if(empty($themeSchema['label'])){
            throw new InvalidThemeSchemaException('Missing label in ' . __METHOD__);
        }

        // check id, create if not set
        if(empty($themeSchema['id'])) {
            $themeSchema['id'] = StringUtils::removeSpecChars($themeSchema['label']);

            // consistency check
            if(!empty($this->ids[$themeSchema['id']])) {
                throw new InvalidThemeSchemaException('Duplicate theme id ' . $themeSchema['id'] . ' in ' . __METHOD__);
            }
            $this->ids[] = $themeSchema['id'];
        }
        // add active true|false
        $themeSchema['active']     = !empty($themeSchema['active']);
        $themeSchema['stylesheet'] = Template::css('themes/' . $this->type . '/' . $themeSchema['id'] . '/theme.css');
        return $themeSchema;
    }


    /**
     * @return array
     */
    public function getAll() {
        return $this->themeSchemata;
    }


    /**
     * Add a theme to the configuration
     *
     * @param $themeSchema
     */
    public function addThemeSchema($themeSchema) {
        $this->themeSchemata[$themeSchema['id']] = $this->addMissingProperties($themeSchema);
    }


    /**
     * Remove a theme from the configuration
     *
     * @param $themeSchema
     */
    public function removeThemeSchema($themeSchema) {
        $themeSchema = $this->addMissingProperties($themeSchema);
        unset($this->themeSchemata[$themeSchema['id']]);
    }

}
