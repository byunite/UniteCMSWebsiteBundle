<?php


namespace Unite\CMSWebsiteBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('unite_cms_website');
        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('multilanguage')->defaultFalse()->end()
                ->arrayNode('site_manager_query')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('site_setting_fields')
                            ->defaultValue('title')
                        ->end()
                        ->scalarNode('find_pages_query')
                            ->defaultValue('findPage')
                        ->end()
                        ->arrayNode('find_pages_filter')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('field')->end()
                                    ->scalarNode('value')->end()
                                    ->scalarNode('operator')->end()
                                    ->scalarNode('cast')->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('find_pages_sort')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('field')->end()
                                    ->scalarNode('order')->end()
                                ->end()
                            ->end()
                            ->defaultValue([[
                                'field' => 'position',
                                'order' => 'ASC',
                            ]])
                        ->end()
                        ->scalarNode('page_fields')
                            ->defaultValue('title, slug { text }')
                        ->end()
                        ->scalarNode('blocks_name')->defaultValue('blocks')->end()
                        ->scalarNode('block_name')->defaultValue('block')->end()
                    ->end()
                ->end()
            ->end();
        return $treeBuilder;
    }
}
