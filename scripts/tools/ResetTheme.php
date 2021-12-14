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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

declare(strict_types = 1);

namespace oat\taoStyles\scripts\tools;

use oat\oatbox\extension\script\ScriptAction;
use oat\tao\helpers\Template;
use oat\tao\model\theme\ConfigurablePlatformTheme;
use oat\tao\model\theme\ThemeServiceInterface;
use common_report_Report as Report;

/**
 * Class ResetTheme
 *
 * Script to reset theme template
 * - only template took under account is 'login-message'
 *
 * Usage
 * `php index.php '\oat\taoStyles\scripts\tools\ResetTheme' --login-message`
 *
 * @package oat\taoStyles\scripts\tools
 */
class ResetTheme extends ScriptAction
{
    protected function provideOptions()
    {
        return [
            'login-message' => [
                'longPrefix' => 'login-message',
                'flag' => true,
                'description' => 'To reset login-message template',
                'required' => false,
                'defaultValue' => false
            ],
        ];
    }

    protected function provideDescription()
    {
        return 'Script to ease reset of current theme templates.';
    }

    /**
     *
     * @return Report
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    protected function run()
    {
        $report = new Report(Report::TYPE_INFO, 'Running theme reset script...');

        if (!$this->hasOption('login-message')) {
            $report->add(new Report(Report::TYPE_ERROR, 'No option given, available candidate for reset: "login-message"'));
            return $report;
        }

        /** @var ThemeServiceInterface $themeService */
        $themeService = $this->getServiceLocator()->get(ThemeServiceInterface::SERVICE_ID);
        $currentTheme = $themeService->getTheme();

        if ($currentTheme instanceof ConfigurablePlatformTheme) {
            $currentOptions = $currentTheme->getOptions();

            if ($this->hasOption('login-message')) {
                $currentOptions[ConfigurablePlatformTheme::TEMPLATES]['login-message'] = Template::getTemplate('blocks/login-message.tpl', 'tao');
                $report->add(
                    new Report(
                        Report::TYPE_SUCCESS,
                        sprintf('"login-message" template has been reset for theme "%s"', $currentTheme->getId())
                    )
                );
            }

            $currentTheme->setOptions($currentOptions);
        }

        $themeService->setTheme($currentTheme, false);
        $this->registerService(ThemeServiceInterface::SERVICE_ID, $themeService);

        $report->add(new Report(Report::TYPE_INFO, '... done'));
        return $report;
    }

}