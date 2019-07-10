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
                ->arrayNode('site_manager_query')
                    ->scalarNode('site_setting_fields')
                        ->defaultValue('title, meta_image { url }, meta_description, text_privacy, text_imprint')
                    ->end()
                    ->scalarNode('find_pages_query')
                        ->defaultValue('findPage')
                    ->end()
                    ->arrayNode('find_pages_sort')
                        ->defaultValue([
                            'field' => 'position',
                            'order' => 'ASC',
                        ])
                    ->end()
                    ->scalarNode('page_fields')
                        ->defaultValue('title, slug { text }, menu_button, meta_image { url }, meta_description')
                    ->end()
                    ->scalarNode('blocks_name')->defaultValue('blocks')->end()
                    ->scalarNode('block_name')->defaultValue('block')->end()
                ->end()
            ->end();
        return $treeBuilder;
    }
}
