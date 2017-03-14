<?php

namespace BestIt\CtCleanUpBundle\Tests;

use BestIt\CtCleanUpBundle\BestItCtCleanUpBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class BestItCtCleanUpBundleTest.
 * @author blange <lange@bestit-online.de>
 * @category Tests
 * @package BestIt\CtCleanUpBundle
 * @version $id$
 */
class BestItCtCleanUpBundleTest extends TestCase
{
    /**
     * The fixture.
     * @var BestItCtCleanUpBundle
     */
    private $fixture = null;

    /**
     * Sets up the test.
     * @return void
     */
    public function setUp()
    {
        $this->fixture = new BestItCtCleanUpBundle();
    }

    /**
     * Checks the instance.
     * @return void
     */
    public function testInstance()
    {
        static::assertInstanceOf(Bundle::class, $this->fixture);
    }
}
