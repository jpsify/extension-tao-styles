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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 *
 */

namespace oat\taoStyles\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\oatbox\log\LoggerAwareTrait;
use oat\tao\model\theme\ThemeService;
use oat\taoStyles\model\service\PersistenceThemeService;

/**
 * This post-installation script creates the ContainerService
 */
class RegisterPersistenceThemeService extends InstallAction
{
    use LoggerAwareTrait;

    /**
     * The default persistence name.
     */
    const DEFAULT_PERSISTENCE_NAME = 'tenantConfigStorage';

    /**
     * The default persistence driver.
     */
    const DEFAULT_PERSISTENCE_DRIVER = 'phpfile';

    /**
     * Sets the ContainerService to TenantService.
     *
     * @param $params
     *
     * @return \common_report_Report
     *
     * @throws \common_exception_Error
     * @throws \common_Exception
     */
    public function __invoke($params)
    {
        /** @var \common_persistence_Manager $persistenceManager */
        $persistenceManager = $this->getServiceManager()->get(\common_persistence_Manager::SERVICE_ID);

        // Getting persistence.
        $persistence = '';
        if (isset($params[0]) && $persistenceManager->hasPersistence($params[0])) {
            $persistence = $params[0];
        }

        // Getting cache persistence.
        $cachePersistence = null;
        if (isset($params[1]) && $persistenceManager->hasPersistence($params[1])) {
            $cachePersistence = $params[1];
        }

        // Getting cache ttl.
        $cacheTtl = null;
        if (isset($params[2]) && is_numeric($params[2])) {
            $cacheTtl = (int)$params[2];
        }

        // If no persistence presented creating a new one if it does not exist.
        if (empty($persistence)) {
            $persistence = static::DEFAULT_PERSISTENCE_NAME;
            if (!$persistenceManager->hasPersistence(static::DEFAULT_PERSISTENCE_NAME)) {
                $persistenceManager->registerPersistence(
                    static::DEFAULT_PERSISTENCE_NAME,
                    [
                        'driver' => static::DEFAULT_PERSISTENCE_DRIVER,
                    ]
                );
            }
        }

        /** @var ThemeService $themeService */
        $themeService = $this->getServiceManager()->get(ThemeService::SERVICE_ID);

        // Gets the options.
        $options = $themeService->getOptions();
        if (isset($options[ThemeService::OPTION_CURRENT])) {
            unset($options[ThemeService::OPTION_CURRENT]);
        }
        if (isset($options[ThemeService::OPTION_AVAILABLE])) {
            unset($options[ThemeService::OPTION_AVAILABLE]);
        }

        // Merge the old options and the new ones.
        $newThemeService = new PersistenceThemeService(
            array_merge(
                $options,
                [
                    PersistenceThemeService::OPTION_PERSISTENCE           => $persistence,
                    PersistenceThemeService::OPTION_CACHE_PERSISTENCE     => $cachePersistence,
                    PersistenceThemeService::OPTION_CACHE_PERSISTENCE_TTL => $cacheTtl,
                ]
            )
        );

        $newThemeService->setServiceManager($this->getServiceManager());

        // Gets the current theme identifier.
        $defaultThemeId = $themeService->getCurrentThemeId();

        // Adds the themes to the PersistenceThemeService.
        foreach($themeService->getAllThemes() as $themeId => $theme) {
            if ($defaultThemeId === $themeId) {
                $newThemeService->setTheme($theme, false);
            } else {
                $newThemeService->addTheme($theme, false);
            }
        }
        $newThemeService->setCurrentTheme($defaultThemeId);

        // Registers the PersistenceThemeService.
        $this->registerService(PersistenceThemeService::SERVICE_ID, $newThemeService);

        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, 'PersistenceThemeService registered');
    }
}
