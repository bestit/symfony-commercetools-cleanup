<?php

namespace BestIt\CtCleanUpBundle;

use Commercetools\Core\Client;
use Commercetools\Core\Config;
use Commercetools\Core\Model\Common\Context;
use Doctrine\Common\Cache\Cache;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

/**
 * Factory for the commercetools client.
 * @author lange <lange@bestit-online.de>
 * @package BestIt\CtOrderExportBundle
 * @subpackage Service
 * @version $id$
 */
class ClientFactory
{
    /**
     * Creates a client.
     * @param array $config
     * @param Cache $cache
     * @param LoggerInterface $logger
     * @return Client
     */
    public static function createClient(
        array $config,
        CacheItemPoolInterface $cache,
        LoggerInterface $logger = null
    ): Client {
        $context = Context::of()->setLanguages(['de'])->setLocale('de_DE')->setGraceful(true);
        $config = Config::fromArray($config)->setContext($context);

        return $logger
            ? Client::ofConfigCacheAndLogger($config, $cache, $logger)
            : Client::ofConfigAndCache($config, $cache);
    }
}
