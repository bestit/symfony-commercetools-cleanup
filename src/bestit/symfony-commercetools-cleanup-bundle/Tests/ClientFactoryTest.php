<?php

namespace BestIt\CtCleanUpBundle\Tests;

use BestIt\CtCleanUpBundle\ClientFactory;
use Commercetools\Core\Client;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class ClientFactoryTest
 * @author lange <lange@bestit-online.de>
 * @category Tests
 * @package BestIt\CtCleanUpBundle
 * @version $id$
 */
class ClientFactoryTest extends WebTestCase
{
    /**
     * Checks if a client is returned.
     * @return void
     */
    public function testCreateClient()
    {
        static::assertInstanceOf(
            Client::class,
            ClientFactory::createClient(
                [
                    'client_id' => uniqid(),
                    'client_secret' => uniqid(),
                    'project' => uniqid()
                ],
                $this->createMock(CacheItemPoolInterface::class)
            )
        );
    }

    /**
     * Checks if the client is created with a logger.
     * @return void
     */
    public function testCreateClientWithLogger()
    {
        static::assertInstanceOf(
            Client::class,
            ClientFactory::createClient(
                [
                    'client_id' => uniqid(),
                    'client_secret' => uniqid(),
                    'project' => uniqid()
                ],
                $this->createMock(CacheItemPoolInterface::class),
                $this->createMock(LoggerInterface::class)
            )
        );
    }

    /**
     * Checks if the service is called correctly.
     * @return void
     */
    public function testServiceDeclaration()
    {
        static::assertInstanceOf(
            Client::class,
            static::createClient()->getContainer()->get('best_it_ct_clean_up.commercetools.client')
        );
    }
}
