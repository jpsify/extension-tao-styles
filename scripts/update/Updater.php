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

namespace oat\taoStyles\scripts\update;

use oat\tao\model\theme\ThemeService;
use oat\taoStyles\model\service\PersistenceThemeService;
use oat\taoStyles\scripts\install\RegisterPersistenceThemeService;

/**
 * TAO Styles Updater.
 * @deprecated use migrations instead. See https://github.com/oat-sa/generis/wiki/Tao-Update-Process
 */
class Updater extends \common_ext_ExtensionUpdater
{
    /**
     * Perform update from $initialVersion to $versionUpdatedTo.
     *
     * @param string $initialVersion
     *
     * @return string $versionUpdatedTo
     *
     * @throws \common_Exception
     */
    public function update($initialVersion)
    {
        $this->skip('0.0.0', '0.1.0');

        if ($this->isVersion('0.1.0')) {
            /** @var \common_persistence_Manager $persistenceManager */
            $persistenceManager = $this->getServiceManager()->get(\common_persistence_Manager::SERVICE_ID);
            $persistences = $persistenceManager->getOption(\common_persistence_Manager::OPTION_PERSISTENCES);

            // Creating a new persistence.
            $persistence = RegisterPersistenceThemeService::DEFAULT_PERSISTENCE_NAME;
            $persistences[$persistence] = [
                'driver' => RegisterPersistenceThemeService::DEFAULT_PERSISTENCE_DRIVER,
            ];

            // Adding the new persistence.
            $persistenceManager->setOption(
                \common_persistence_Manager::OPTION_PERSISTENCES,
                $persistences
            );

            // Registering to the config.
            $this->getServiceManager()->register(
                \common_persistence_Manager::SERVICE_ID,
                $persistenceManager
            );

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
                        PersistenceThemeService::OPTION_CACHE_PERSISTENCE     => null,
                        PersistenceThemeService::OPTION_CACHE_PERSISTENCE_TTL => null,
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

            // Registers the PersistenceThemeService.
            $this->getServiceManager()->register(PersistenceThemeService::SERVICE_ID, $newThemeService);

            $this->setVersion('0.2.0');
        }

        $this->skip('0.2.0','3.1.0');
        
        //Updater files are deprecated. Please use migrations.
        //See: https://github.com/oat-sa/generis/wiki/Tao-Update-Process

        $this->setVersion($this->getExtension()->getManifest()->getVersion());
    }
}
