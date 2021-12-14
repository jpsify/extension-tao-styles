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
 *
 *
 */

namespace oat\taoStyles\controller;

use oat\tao\helpers\Layout;
use oat\tao\helpers\Template;
use oat\tao\model\routing\AnnotationReader\security;
use oat\tao\model\service\ContainerService;
use oat\tao\model\theme\ConfigurablePlatformTheme;
use oat\tao\model\theme\ThemeConverter;
use oat\tao\model\theme\ThemeService;
use oat\tao\model\theme\ThemeServiceInterface;
use oat\taoStyles\helpers\StyleHelper;
use oat\taoStyles\model\themes\ThemeSchemaCollection;
use oat\taoStyles\model\images\ImageProcessor;

/**
 * Class Main
 *
 * @package oat\taoStyles\controller
 */
class Main extends \tao_actions_CommonModule
{

    /**
     * This extension name
     */
    const EXTENSION_NAME = 'taoStyles';

    /**
     * This class should handle platform themes only
     */
    const THEME_TYPE = 'platform';

    /**
     * The id of the regular default theme
     */
    const PLATFORM_DEFAULT_THEME = 'default';


    /**
     * Main constructor.
     * @security("hide");
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return ThemeServiceInterface
     */
    protected function getThemeService()
    {
        return $this->getServiceManager()->get(ThemeService::SERVICE_ID);
    }


    /**
     * Main action
     */
    public function index()
    {
        $themes = (new ThemeSchemaCollection(static::THEME_TYPE))->getAll();

        // make sure you always have an instance of ConfigurablePlatformTheme
        // don't save the theme though
        $currentTheme = ThemeConverter::convertFromLegacyTheme($this->getThemeService()->getTheme());

        foreach($themes as $key => $themeSchema) {
            $themes[$key]['selected'] = $themeSchema['id'] === $currentTheme->getId();
        }

        // Don't try to load the non existing css.
        $themes[static::PLATFORM_DEFAULT_THEME]['stylesheet'] = '';

        $settings = [
            ConfigurablePlatformTheme::LOGO_URL         => Layout::getLogoUrl(),
            ConfigurablePlatformTheme::ID               => $currentTheme->getId(),
            ConfigurablePlatformTheme::OPERATED_BY      => $currentTheme->getOperatedBy(),
            'logo' . ConfigurablePlatformTheme::LINK    => $currentTheme->getLink(),
            'logo' . ConfigurablePlatformTheme::MESSAGE => $currentTheme->getMessage(),
        ];


        foreach($settings as $key => $setting) {
            $this->setData($key, $setting);
        }

        $this->setData('themes', $themes);
        $this->setData('preview-svg', Template::img('preview/theme-preview.svg', static::EXTENSION_NAME));
        $this->setView('index.tpl');
    }


    /**
     * Upload and resize the logo
     *
     * @return bool
     * @throws \Exception
     */
    public function processLogo()
    {
        if(!\tao_helpers_Request::isAjax()) {
            throw new \Exception('Wrong request mode');
        }

        $imageProcessor = new ImageProcessor();

        // upload result is either the path to the zip file or an array with errors
        $uploadResult = $imageProcessor->uploadLogo();
        if(!empty($uploadResult['error'])){
            $this->returnJson($uploadResult);
            return false;
        }

        $resizedData = $imageProcessor->buildResizedImage($uploadResult);

        $this->returnJson($resizedData);
        return true;
    }


    /**
     * Register style as theme
     *
     * @return bool
     * @throws \Exception
     */
    public function save()
    {
        if(!\tao_helpers_Request::isAjax()) {
            throw new \Exception('Wrong request mode');
        }

        $currentTheme  = $this->getThemeService()->getTheme();
        $themeData     = $this->getRequestParameter('theme');
        $customThemeId = $this->getCustomThemeId();

        // Theme specific details resolution.
        $themeData[ConfigurablePlatformTheme::OPERATED_BY] = [
            'name'  => isset($themeData['operatedByName'])  ? $themeData['operatedByName'] : '',
            'email' => isset($themeData['operatedByEmail']) ? $themeData['operatedByEmail'] : '',
        ];
        $themeData[ConfigurablePlatformTheme::EXTENSION_ID] = static::EXTENSION_NAME;

        // Empty the logo url if it is the default logo url.
        if (isset($themeData[ConfigurablePlatformTheme::LOGO_URL]) &&
            $themeData[ConfigurablePlatformTheme::LOGO_URL] === Layout::getDefaultLogoUrl()
        ) {
            $themeData[ConfigurablePlatformTheme::LOGO_URL] = '';
        }

        // If there was no theme selected, fallback to the current one.
        if (empty($themeData[ConfigurablePlatformTheme::ID])) {
            $newTheme = $currentTheme;
            if (isset($themeData[ConfigurablePlatformTheme::LOGO_URL])) {
                $newTheme->setOption(
                    ConfigurablePlatformTheme::LOGO_URL,
                    $themeData[ConfigurablePlatformTheme::LOGO_URL]
                );
            }

            if (isset($themeData[ConfigurablePlatformTheme::LINK])) {
                $validator = new \tao_helpers_form_validators_Url();
                if ($validator->evaluate($themeData[ConfigurablePlatformTheme::LINK])) {
                    $newTheme->setOption(
                        ConfigurablePlatformTheme::LINK,
                        $themeData[ConfigurablePlatformTheme::LINK]
                    );
                }
            }

            if (isset($themeData[ConfigurablePlatformTheme::MESSAGE])) {
                $newTheme->setOption(
                    ConfigurablePlatformTheme::MESSAGE,
                    $themeData[ConfigurablePlatformTheme::MESSAGE]
                );
            }

            $newTheme->setOption(
                ConfigurablePlatformTheme::EXTENSION_ID,
                $themeData[ConfigurablePlatformTheme::EXTENSION_ID]
            );

            $newTheme->setOption(
                ConfigurablePlatformTheme::OPERATED_BY,
                $themeData[ConfigurablePlatformTheme::OPERATED_BY]
            );

            // Overload theme id if it's necessary.
            if ($customThemeId !== '') {
                $newTheme->setOption(ConfigurablePlatformTheme::ID, $customThemeId);
            }
        }
        else {
            if ($themeData[ConfigurablePlatformTheme::ID] === static::PLATFORM_DEFAULT_THEME) {
                unset($themeData[ConfigurablePlatformTheme::STYLESHEET]);
            }
            else {
                $themeData[ConfigurablePlatformTheme::STYLESHEET] = StyleHelper::getEncodedStylesheet(
                    static::THEME_TYPE,
                    $themeData[ConfigurablePlatformTheme::ID]
                );
            }

            // Overload theme id if it's necessary.
            if ($customThemeId !== '') {
                $themeData[ConfigurablePlatformTheme::ID] = $customThemeId;
            }

            $newTheme = new ConfigurablePlatformTheme($themeData);

            // Inherits templates if they're not set yet.
            if ($currentTheme instanceof ConfigurablePlatformTheme) {
                if ($currentTheme->hasOption(ConfigurablePlatformTheme::TEMPLATES)) {
                    $newTheme->setOption(
                        ConfigurablePlatformTheme::TEMPLATES,
                        array_merge(
                            (array)$currentTheme->getOption(ConfigurablePlatformTheme::TEMPLATES),
                            (array)$newTheme->getOption(ConfigurablePlatformTheme::TEMPLATES)
                        )
                    );
                }
            }
        }

        $this->getThemeService()->setTheme($newTheme, false);

        $this->getServiceManager()->register(ThemeService::SERVICE_ID, $this->getThemeService());

        $response = ($this->getThemeService()->getCurrentThemeId() === $newTheme->getId())
            ? ['type' => 'success', 'msg' => __('Your theme has been saved')]
            : ['type' => 'error', 'msg' => __('An error occurred while saving your theme')];

        $this->returnJson($response);
        return true;
    }

    /**
     * Returns the custom theme id.
     *
     * @return string
     */
    public function getCustomThemeId()
    {
        /** @var ContainerService $containerService */
        $containerService = $this->getServiceManager()->get(ContainerService::SERVICE_ID);

        // Gets custom theme id if it's needed.
        if (method_exists($containerService, 'getThemeId')) {
            return $containerService->getThemeId();
        }

        return '';
    }
}
