<?php

declare(strict_types=1);

namespace BestIt\CtCleanUpBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration for the bundle.
 *
 * @author blange <lange@bestit-online.de>
 * @package BestIt\CtCleanUpBundle\DependencyInjection
 * @version $id$
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Create a single type node.
     * @param string $type
     * @return ArrayNodeDefinition
     */
    private function createTypeNode(string $type): ArrayNodeDefinition
    {
        $node = (new TreeBuilder())->root($type);

        $node
            ->info(
                'Define your ' . $type . ' predicates (https://dev.commercetools.com/http-api.html). This ' .
                'predicates are concatinated with an or.'
            )
            ->normalizeKeys(false)
            ->prototype('scalar')->end()
        ->end();

        return $node;
    }

    /**
     * Generates the configuration tree builder.
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $predicatedNode = $builder->root('best_it_ct_clean_up')
            ->children()
                ->arrayNode('commercetools_client')
                    ->isRequired()
                    ->children()
                        ->scalarNode('id')->cannotBeEmpty()->isRequired()->end()
                        ->scalarNode('secret')->cannotBeEmpty()->isRequired()->end()
                        ->scalarNode('project')->cannotBeEmpty()->isRequired()->end()
                        ->scalarNode('scope')->cannotBeEmpty()->isRequired()->end()
                    ->end()
                ->end()
                ->scalarNode('logger')
                    ->info('Please provide the service id for your logging service.')
                ->end()
                ->arrayNode('predicates');

        $types = [
            'cart',
            'category',
            'customer',
            'product',
            'order'
        ];

        foreach ($types as $type) {
            $predicatedNode->append($this->createTypeNode($type));
        }

        $predicatedNode->end()->end()->end();

        return $builder;
    }
}
