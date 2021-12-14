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

namespace oat\taoStyles\test\model\service;


use oat\oatbox\service\ServiceManager;
use oat\tao\model\theme\ConfigurablePlatformTheme;
use oat\tao\model\theme\Theme;
use oat\taoStyles\model\service\PersistenceThemeService;
use oat\taoStyles\test\InvokeNonPublicMethodTrait;
use oat\generis\test\TestCase;
use oat\generis\test\MockObject;

class PersistenceThemeServiceTest extends TestCase
{
    use InvokeNonPublicMethodTrait;

    /**
     * @var string
     */
    protected $defaultThemeIdFixture = 'testTheme';

    /**
     * Test for the getCurrentThemeId method.
     *
     * @param $expected
     * @param $cacheCalled
     * @param $cacheFixture
     * @param $persistenceCalled
     * @param $persistenceFixture
     * @param $setCacheCalled
     *
     * @dataProvider provideDataForGetCurrentThemeId
     */
    public function testGetCurrentThemeId(
        $expected, $cacheCalled, $cacheFixture, $persistenceCalled, $persistenceFixture, $setCacheCalled
    )
    {
        $instanceMock = $this->getMockBuilder(PersistenceThemeService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDefaultThemeIdFromCache', 'getDefaultThemeIdFromPersistence', 'setDefaultThemeIdToCache'])
            ->getMock()
        ;
        $instanceMock->expects($cacheCalled)
            ->method('getDefaultThemeIdFromCache')
            ->willReturn($cacheFixture)
        ;
        $instanceMock->expects($persistenceCalled)
            ->method('getDefaultThemeIdFromPersistence')
            ->willReturn($persistenceFixture)
        ;
        $instanceMock->expects($setCacheCalled)
            ->method('setDefaultThemeIdToCache')
            ->willReturn(true)
        ;

        $this->assertEquals(
            $expected,
            $instanceMock->getCurrentThemeId()
        );
    }

    /**
     * Data provider for the getCurrentThemeId method.
     *
     * @return array
     */
    public function provideDataForGetCurrentThemeId()
    {
        return [
            'noCache' => [
                $this->defaultThemeIdFixture,
                $this->once(),
                false,
                $this->once(),
                $this->defaultThemeIdFixture,
                $this->once(),
            ],
            'fromCache' => [
                $this->defaultThemeIdFixture,
                $this->once(),
                $this->defaultThemeIdFixture,
                $this->exactly(0),
                $this->defaultThemeIdFixture,
                $this->exactly(0),
            ],
            'noData' => [
                false,
                $this->once(),
                false,
                $this->once(),
                false,
                $this->once(),
            ],
        ];
    }

    /**
     * Test for the getDefaultThemeIdFromCache method.
     *
     * @param $expected
     * @param $cachePersistenceMock
     * @param $cacheCalled
     *
     * @dataProvider provideDataForGetDefaultThemeIdFromCache
     */
    public function testGetDefaultThemeIdFromCache($expected, $cachePersistenceMock, $cacheCalled)
    {
        $instanceMock = $this->getMockBuilder(PersistenceThemeService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCachePersistence'])
            ->getMock()
        ;
        $instanceMock->expects($cacheCalled)
            ->method('getCachePersistence')
            ->willReturn($cachePersistenceMock)
        ;

        $this->assertEquals(
            $expected,
            $this->invokeMethod( $instanceMock, 'getDefaultThemeIdFromCache')
        );
    }

    /**
     * Data provider for the getDefaultThemeIdFromCache method.
     *
     * @return array
     */
    public function provideDataForGetDefaultThemeIdFromCache()
    {
        return [
            'noPersistence' => [
                false,
                null,
                $this->once(),
            ],
            'persistence' => [
                $this->defaultThemeIdFixture,
                $this->getPersistenceMockWithGet($this->defaultThemeIdFixture),
                $this->exactly(2),
            ],
        ];
    }

    /**
     * Test for the getDefaultThemeIdFromPersistence method.
     */
    public function testGetDefaultThemeIdFromPersistence()
    {
        $persistenceMock = $this->getMockForAbstractClass(\common_persistence_KvDriver::class);
        $persistenceMock->expects($this->once())
            ->method('get')
            ->willReturn($this->defaultThemeIdFixture)
        ;

        $instanceMock = $this->getMockBuilder(PersistenceThemeService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPersistence'])
            ->getMock()
        ;
        $instanceMock->expects($this->once())
            ->method('getPersistence')
            ->willReturn($persistenceMock)
        ;

        $this->assertEquals(
            $this->defaultThemeIdFixture,
            $this->invokeMethod( $instanceMock, 'getDefaultThemeIdFromPersistence')
        );
    }

    /**
     * Test for the getAllThemes method.
     *
     * @param $expected
     * @param $cacheCalled
     * @param $cacheFixture
     * @param $persistenceCalled
     * @param $persistenceFixture
     * @param $setCacheCalled
     *
     * @dataProvider provideDataForGetAllThemes
     */
    public function testGetAllThemes(
        $expected, $cacheCalled, $cacheFixture, $persistenceCalled, $persistenceFixture, $setCacheCalled
    )
    {
        $instanceMock = $this->getMockBuilder(PersistenceThemeService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getThemesFromCache', 'getThemesFromPersistence', 'setThemesToCache'])
            ->getMock()
        ;
        $instanceMock->expects($cacheCalled)
            ->method('getThemesFromCache')
            ->willReturn($cacheFixture)
        ;
        $instanceMock->expects($persistenceCalled)
            ->method('getThemesFromPersistence')
            ->willReturn($persistenceFixture)
        ;
        $instanceMock->expects($setCacheCalled)
            ->method('setThemesToCache')
            ->willReturn(true)
        ;

        $this->assertEquals(
            $expected,
            $instanceMock->getAllThemes()
        );
    }

    /**
     * Data provider for the getAllThemes method.
     *
     * @return array
     */
    public function provideDataForGetAllThemes()
    {
        return [
            'noCache' => [
                [$this->getThemeFixture()],
                $this->once(),
                false,
                $this->once(),
                [$this->getThemeFixture()],
                $this->once(),
            ],
            'fromCache' => [
                [$this->getThemeFixture()],
                $this->once(),
                [$this->getThemeFixture()],
                $this->exactly(0),
                [$this->getThemeFixture()],
                $this->exactly(0),
            ],
            'noData' => [
                false,
                $this->once(),
                false,
                $this->once(),
                false,
                $this->once(),
            ],
        ];
    }

    /**
     * Test for the getThemesFromCache method.
     *
     * @param $expected
     * @param $cachePersistenceMock
     * @param $cacheCalled
     * @param $unSerializeCalled
     *
     * @dataProvider provideDataForGetThemesFromCache
     */
    public function testGetThemesFromCache($expected, $cachePersistenceMock, $cacheCalled, $unSerializeCalled)
    {
        $instanceMock = $this->getMockBuilder(PersistenceThemeService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCachePersistence', 'unSerializeThemes'])
            ->getMock()
        ;
        $instanceMock->expects($cacheCalled)
            ->method('getCachePersistence')
            ->willReturn($cachePersistenceMock)
        ;
        $instanceMock->expects($unSerializeCalled)
            ->method('unSerializeThemes')
            ->willReturn([$this->getThemeFixture()])
        ;

        $this->assertEquals(
            $expected,
            $this->invokeMethod( $instanceMock, 'getThemesFromCache')
        );
    }

    /**
     * Data provider for the getThemesFromCache method.
     *
     * @return array
     */
    public function provideDataForGetThemesFromCache()
    {
        return [
            'noPersistence' => [
                false,
                null,
                $this->once(),
                $this->exactly(0),
            ],
            'notInCache' => [
                false,
                $this->getPersistenceMockWithGet(false),
                $this->exactly(2),
                $this->exactly(0),
            ],
            'persistence' => [
                [$this->getThemeFixture()],
                $this->getPersistenceMockWithGet([$this->getThemeFixture()]),
                $this->exactly(2),
                $this->once(),
            ],
        ];
    }

    /**
     * Test for the getThemesFromPersistence method.
     *
     * @param $expected
     * @param $persistenceMock
     * @param $unSerializeCalled
     *
     * @dataProvider provideDataForGetThemesFromPersistence
     */
    public function testGetThemesFromPersistence($expected, $persistenceMock, $unSerializeCalled)
    {
        $instanceMock = $this->getMockBuilder(PersistenceThemeService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPersistence', 'unSerializeThemes'])
            ->getMock()
        ;
        $instanceMock->expects($this->once())
            ->method('getPersistence')
            ->willReturn($persistenceMock)
        ;
        $instanceMock->expects($unSerializeCalled)
            ->method('unSerializeThemes')
            ->willReturn([$this->getThemeFixture()])
        ;

        $this->assertEquals(
            $expected,
            $this->invokeMethod( $instanceMock, 'getThemesFromPersistence')
        );
    }

    /**
     * Data provider for the getThemesFromPersistence method.
     *
     * @return array
     */
    public function provideDataForGetThemesFromPersistence()
    {
        return [
            'notInPersistence' => [
                false,
                $this->getPersistenceMockWithGet(false),
                $this->exactly(0),
            ],
            'persistence' => [
                [$this->getThemeFixture()],
                $this->getPersistenceMockWithGet([$this->getThemeFixture()]),
                $this->once(),
            ],
        ];
    }

    /**
     * Test for the serializeThemes method.
     */
    public function testSerializeThemes()
    {
        $theme = new ConfigurablePlatformTheme([
            'id'    => 'AppleDefaultTheme',
            'label' => 'Apple Default Theme',
            'data'  => [
                'logo-url' => 'http://www.pngmart.com/files/1/Apple-Fruit-PNG-File.png',
                'link'     => '',
                'message'  => '',
            ]
        ]);
        $themes   = [$theme];
        $expected = '[{"class":"oat\\\\tao\\\\model\\\\theme\\\\ConfigurablePlatformTheme","options":{"id":"AppleDefaultTheme","label":"Apple Default Theme","data":{"logo-url":"http:\/\/www.pngmart.com\/files\/1\/Apple-Fruit-PNG-File.png","link":"","message":""}}}]';

        $instanceMock = $this->getMockBuilder(PersistenceThemeService::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->assertEquals(
            $expected,
            $this->invokeMethod($instanceMock, 'serializeThemes', [$themes])
        );
    }

    /**
     * Test for the unSerializeThemes method.
     */
    public function testUnSerializeThemes()
    {
        $encodedThemesFixture = '[{"class":"oat\\\\tao\\\\model\\\\theme\\\\ConfigurablePlatformTheme","options":{"id":"AppleDefaultTheme","label":"Apple Default Theme","data":{"logo-url":"http:\/\/www.pngmart.com\/files\/1\/Apple-Fruit-PNG-File.png","link":"","message":""}}}]';

        $theme = new ConfigurablePlatformTheme([
            'id'    => 'AppleDefaultTheme',
            'label' => 'Apple Default Theme',
            'data'  => [
                'logo-url' => 'http://www.pngmart.com/files/1/Apple-Fruit-PNG-File.png',
                'link'     => '',
                'message'  => '',
            ]
        ]);
        $decodedThemes = [$theme];

        // Sets the ServiceManager mock.
        $serviceManagerMock = $this->getMockBuilder(ServiceManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['build'])
            ->getMock()
        ;
        $serviceManagerMock->expects($this->once())
            ->method('build')
            ->willReturn($theme)
        ;

        // Sets the instance.
        $instanceMock = $this->getMockBuilder(PersistenceThemeService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getServiceManager'])
            ->getMock()
        ;
        $instanceMock->expects($this->once())
            ->method('getServiceManager')
            ->willReturn($serviceManagerMock)
        ;

        $this->assertEquals(
            $decodedThemes,
            $this->invokeMethod($instanceMock, 'unSerializeThemes', [$encodedThemesFixture])
        );
    }

    /**
     * Test for the getCacheTtl method.
     *
     * @param $expected
     * @param $hasOption
     * @param $getOption
     * @param $getOptionCalled
     *
     * @dataProvider provideDataForGetCacheTtl
     */
    public function testGetCacheTtl($expected, $hasOption, $getOption, $getOptionCalled)
    {
        $instanceMock = $this->getMockBuilder(PersistenceThemeService::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasOption', 'getOption'])
            ->getMock()
        ;
        $instanceMock->expects($this->once())
            ->method('hasOption')
            ->willReturn($hasOption)
        ;
        $instanceMock->expects($getOptionCalled)
            ->method('getOption')
            ->willReturn($getOption)
        ;

        $this->assertEquals(
            $expected,
            $this->invokeMethod($instanceMock, 'getCacheTtl')
        );
    }

    /**
     * Data provider for the getCacheTtl method.
     *
     * @return array
     */
    public function provideDataForGetCacheTtl()
    {
        $cacheTtlFixture = 30;
        return [
            'default' => [
                PersistenceThemeService::CACHE_PERSISTENCE_DEFAULT_TTL,
                false,
                $cacheTtlFixture,
                $this->exactly(0),
            ],
            'hasValue' => [
                $cacheTtlFixture,
                true,
                $cacheTtlFixture,
                $this->once(),
            ],
        ];
    }

    /**
     * Returns  the Theme fixture.
     * 
     * @return MockObject
     */
    protected function getThemeFixture()
    {
        return $this->getMockForAbstractClass(Theme::class);
    }

    /**
     * Returns a persistence mock with get method which returns the requested value.
     *
     * @param $value
     *
     * @return MockObject
     */
    protected function getPersistenceMockWithGet($value)
    {
        $persistenceMock = $this->getMockForAbstractClass(\common_persistence_KvDriver::class);
        $persistenceMock->expects($this->once())
            ->method('get')
            ->willReturn($value);

        return $persistenceMock;
    }
}
