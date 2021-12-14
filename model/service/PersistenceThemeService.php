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
 */

namespace oat\taoStyles\model\service;


use oat\oatbox\Configurable;
use oat\tao\model\theme\Theme;
use oat\tao\model\theme\ThemeServiceAbstract;

class PersistenceThemeService extends ThemeServiceAbstract
{
    /**
     * The default ttl of the cache persistence.
     */
    const CACHE_PERSISTENCE_DEFAULT_TTL = null;

    /**
     * The key-value persistence config option.
     */
    const OPTION_PERSISTENCE = 'persistence';

    /**
     * The cache persistence config option.
     */
    const OPTION_CACHE_PERSISTENCE = 'cachePersistence';

    /**
     * The cache persistence ttl config option.
     */
    const OPTION_CACHE_PERSISTENCE_TTL = 'cachePersistenceTtl';

    /**
     * @var string   The default theme identifier.
     */
    private $defaultThemeId;

    /**
     * @var Theme[]   The available themes.
     */
    private $themes;

    /**
     * @var \common_persistence_KvDriver
     */
    private $persistence;

    /**
     * @var \common_persistence_KvDriver
     */
    private $cachePersistence;

    /**
     * @inheritdoc
     */
    public function getCurrentThemeId()
    {
        if ($this->defaultThemeId === null) {
            $this->defaultThemeId = $this->getDefaultThemeIdFromCache();
            if ($this->defaultThemeId === false) {
                $this->defaultThemeId = $this->getDefaultThemeIdFromPersistence();
                $this->setDefaultThemeIdToCache($this->defaultThemeId);
            }
        }

        return $this->defaultThemeId;
    }

    /**
     * Returns the default theme id from cache.
     *
     * @return string
     */
    protected function getDefaultThemeIdFromCache()
    {
        if ($this->getCachePersistence() === null) {
            return false;
        }

        return $this->getCachePersistence()->get(static::OPTION_CURRENT);
    }

    /**
     * Returns the default theme id from persistence.
     *
     * @return string
     */
    protected function getDefaultThemeIdFromPersistence()
    {
        return $this->getPersistence()->get(static::OPTION_CURRENT);
    }

    /**
     * @inheritdoc
     */
    public function getAllThemes()
    {
        if ($this->themes === null) {
            $this->themes = $this->getThemesFromCache();
            if ($this->themes === false) {
                $this->themes = $this->getThemesFromPersistence();
                $this->setThemesToCache($this->themes);
            }
        }

        return $this->themes;
    }

    /**
     * Returns the themes from cache.
     *
     * @return Theme[]|bool
     */
    protected function getThemesFromCache()
    {
        if ($this->getCachePersistence() === null) {
            return false;
        }

        $themes = $this->getCachePersistence()->get(static::OPTION_AVAILABLE);
        if ($themes === false) {
            return false;
        }

        return $this->unSerializeThemes($themes);
    }

    /**
     * Returns the themes from persistence.
     *
     * @return Theme[]|bool
     */
    protected function getThemesFromPersistence()
    {
        $themes = $this->getPersistence()->get(static::OPTION_AVAILABLE);
        if ($themes === false) {
            return false;
        }

        return $this->unSerializeThemes($themes);
    }

    /**
     * @inheritdoc
     */
    public function addTheme(Theme $theme, $protectAlreadyExistingThemes = true)
    {
        $themes  = $this->getAllThemes();
        $themeId = $theme->getId();

        if ($protectAlreadyExistingThemes) {
            $themeId = $this->getUniqueId($theme);
        }

        $themes[$themeId] = $theme;

        $this->setThemesToPersistence($themes);
        $this->setThemesToCache($themes);
        $this->themes = $themes;
    }

    /**
     * Sets the themes to cache.
     *
     * @param Theme[]|bool $themes
     *
     * @return bool
     */
    protected function setThemesToCache($themes)
    {
        if ($this->getCachePersistence() === null) {
            return false;
        }

        return $this->getCachePersistence()->set(
            static::OPTION_AVAILABLE,
            $this->serializeThemes($themes),
            $this->getCacheTtl()
        );
    }

    /**
     * Sets the themes to persistence.
     *
     * @param Theme[] $themes
     *
     * @return bool
     */
    protected function setThemesToPersistence(array $themes)
    {
        return $this->getPersistence()->set(
            static::OPTION_AVAILABLE,
            $this->serializeThemes($themes)
        );
    }

    /**
     * @inheritdoc
     */
    public function setCurrentTheme($themeId)
    {
        if (!$this->hasTheme($themeId)) {
            throw new \common_exception_Error('Theme '. $themeId .' not found');
        }

        $this->setDefaultThemeIdToPersistence($themeId);
        $this->setDefaultThemeIdToCache($themeId);
        $this->defaultThemeId = $themeId;
    }

    /**
     * Sets the default theme id to cache.
     *
     * @param string $themeId
     *
     * @return bool
     */
    protected function setDefaultThemeIdToCache($themeId)
    {
        if ($this->getCachePersistence() === null) {
            return false;
        }

        return $this->getCachePersistence()->set(static::OPTION_CURRENT, $themeId, $this->getCacheTtl());
    }

    /**
     * Sets the default theme id to persistence.
     *
     * @param string $themeId
     *
     * @return bool
     */
    protected function setDefaultThemeIdToPersistence($themeId)
    {
        return $this->getPersistence()->set(static::OPTION_CURRENT, $themeId);
    }

    /**
     * @inheritdoc
     */
    public function removeThemeById($themeId)
    {
        if (!$this->hasTheme($themeId)) {
            return false;
        }

        $this->removeThemeByIdFromCache($themeId);
        $this->removeThemeByIdFromPersistence($themeId);
        $this->themes = null;

        return true;
    }

    /**
     * Removes the Theme identified by the requested identifier from cache.
     *
     * @param string $themeId
     *
     * @return bool
     */
    protected function removeThemeByIdFromCache($themeId)
    {
        $themes = $this->getAllThemes();
        unset($themes[$themeId]);

        return $this->setThemesToCache($themes);
    }

    /**
     * Removes the Theme identified by the requested identifier from persistence.
     *
     * @param string $themeId
     *
     * @return bool
     */
    protected function removeThemeByIdFromPersistence($themeId)
    {
        $themes = $this->getAllThemes();
        unset($themes[$themeId]);

        return $this->setThemesToPersistence($themes);
    }

    /**
     * Serializes the themes to sting.
     *
     * @param Theme[] $themes
     *
     * @return string
     */
    protected function serializeThemes(array $themes)
    {
        $encodedThemes = [];
        foreach ($themes as $themeId => $theme) {
            $encodedThemes[$themeId] = [
                static::THEME_CLASS_OFFSET   => get_class($theme),
                static::THEME_OPTIONS_OFFSET => ($theme instanceof Configurable) ? $theme->getOptions() : []
            ];
        }

        return json_encode($encodedThemes);
    }

    /**
     * Unserializes the themes from sting.
     *
     * @param string $themes
     *
     * @return Theme[]
     */
    protected function unSerializeThemes($themes)
    {
        $decodedThemes = (array)json_decode($themes, true);
        $themes        = [];
        foreach ($decodedThemes as $themeId => $theme) {
            $themes[$themeId] = $this->getServiceManager()->build(
                $theme[static::THEME_CLASS_OFFSET],
                $theme[static::THEME_OPTIONS_OFFSET]
            );
        }

        return $themes;
    }

    /**
     * Returns the persistence.
     *
     * @return \common_persistence_KvDriver
     */
    protected function getPersistence()
    {
        if (is_null($this->persistence)) {
            $persistenceOption = $this->getOption(static::OPTION_PERSISTENCE);
            $this->persistence = (is_object($persistenceOption))
                ? $persistenceOption
                : \common_persistence_KeyValuePersistence::getPersistence($persistenceOption);
        }

        return $this->persistence;
    }

    /**
     * Returns the cache persistence.
     *
     * @return \common_persistence_KvDriver
     */
    protected function getCachePersistence()
    {
        if (is_null($this->cachePersistence) && $this->hasOption(static::OPTION_CACHE_PERSISTENCE)) {
            $persistenceOption      = $this->getOption(static::OPTION_CACHE_PERSISTENCE);
            $this->cachePersistence = (is_object($persistenceOption))
                ? $persistenceOption
                : \common_persistence_KeyValuePersistence::getPersistence($persistenceOption);
        }

        return $this->cachePersistence;
    }

    /**
     * Returns the cache persistence's ttl.
     *
     * @return int|null
     */
    public function getCacheTtl()
    {
        if ($this->hasOption(static::OPTION_CACHE_PERSISTENCE_TTL)) {
            $cacheTtl = $this->getOption(static::OPTION_CACHE_PERSISTENCE_TTL);
            if (!is_null($cacheTtl)) {
                return $cacheTtl;
            }
        }

        return static::CACHE_PERSISTENCE_DEFAULT_TTL;
    }
}
