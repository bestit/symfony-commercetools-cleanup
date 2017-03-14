<?php

use BestIt\CtCleanUpBundle\BestItCtCleanUpBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Sensio\Bundle\DistributionBundle\SensioDistributionBundle;
use Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\Config\Loader\LoaderInterface;

/**
 * Tests the app kernel.
 * @author lange <lange@bestit-online.de>
 * @category Tests
 * @package Bh\AppBundle
 * @return void
 */
class AppKernelTest extends TestCase
{
    /**
     * The tested environment.
     * @var string
     */
    const TESTED_ENV = 'dev';

    /**
     * The tested class.
     * @var AppKernel
     */
    private $fixture = null;

    /**
     * Dataprovider for required bundles.
     * @return array
     */
    public function getRequiredBundles(): array
    {
        return [
            [BestItCtCleanUpBundle::class],
            [DebugBundle::class],
            [FrameworkBundle::class],
            [MonologBundle::class],
            [SensioDistributionBundle::class],
            [SensioFrameworkExtraBundle::class],
            [SensioGeneratorBundle::class]
        ];
    }

    /**
     * Sets up the test.
     * @return void
     */
    public function setUp()
    {
        $this->fixture = new AppKernel(self::TESTED_ENV, false);
    }

    /**
     * Checks the return of the getter.
     * @return void
     */
    public function testGetCacheDir()
    {
        static::assertSame(
            realpath(__DIR__ . '/../') . '/var/cache/' . self::TESTED_ENV,
            $this->fixture->getCacheDir()
        );
    }


    /**
     * Checks the return of the getter.
     * @return void
     */
    public function testGetLogDir()
    {
        static::assertSame(
            realpath(__DIR__ . '/../') . '/var/logs',
            $this->fixture->getLogDir()
        );
    }

    /**
     * Checks the return of the getter.
     * @return void
     */
    public function testGetRootDir()
    {
        static::assertSame(
            realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . 'app',
            $this->fixture->getRootDir()
        );
    }

    /**
     * Checks if the given bundle class is registered.
     * @dataProvider getRequiredBundles
     * @return void
     */
    public function testRegisterBundlesRequiredOnes(string $bundleClass)
    {
        $bundles = $this->fixture->registerBundles();

        foreach ($bundles as $bundle) {
            if (is_a($bundle, $bundleClass)) {
                return true;
            }
        }

        static::fail(sprintf('Bundle %s was not registered.', $bundleClass));
    }

    /**
     * Checks if the config loader is registered correctly.
     * @return void
     */
    public function testRegisterContainerConfiguration()
    {
        $mock = static::createMock(LoaderInterface::class);

        $mock
            ->expects(static::once())
            ->method('load')
            ->with(realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . 'app/config/config_' . self::TESTED_ENV . '.yml');

        $this->fixture->registerContainerConfiguration($mock);
    }
}
