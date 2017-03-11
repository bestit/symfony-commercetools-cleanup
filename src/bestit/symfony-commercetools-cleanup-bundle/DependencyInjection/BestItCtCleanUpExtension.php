<?php

namespace BestIt\CtCleanUpBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Loading the bundle.
 * @author blange <lange@bestit-online.de>
 * @package BestIt\CtCleanUpBundle
 * @subpackage DependencyInjection
 * @version $id$
 */
class BestItCtCleanUpExtension extends Extension
{
    /**
     * Loads a specific configuration.
     * @param array $configs An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setAlias('best_it_ct_clean_up.logger', $config['logger']);

        $container->setParameter('best_it_ct_clean_up.predicates', $config['predicates'] ?? []);

        $container->setParameter(
            'best_it_ct_clean_up.commercetools.client.id',
            (string) @ $config['commercetools_client']['id']
        );

        $container->setParameter(
            'best_it_ct_clean_up.commercetools.client.secret',
            (string) @ $config['commercetools_client']['secret']
        );

        $container->setParameter(
            'best_it_ct_clean_up.commercetools.client.project',
            (string) @ $config['commercetools_client']['project']
        );

        $container->setParameter(
            'best_it_ct_clean_up.commercetools.client.scope',
            (string) @ $config['commercetools_client']['scope']
        );
    }
}
