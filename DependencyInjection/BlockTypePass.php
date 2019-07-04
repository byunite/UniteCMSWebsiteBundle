<?php


namespace Unite\CMSWebsiteBundle\DependencyInjection;

use Unite\CMSWebsiteBundle\Services\BlockTypeManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class BlockTypePass implements CompilerPassInterface
{

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if(!$container->has(BlockTypeManager::class)) {
            return;
        }

        $definition = $container->findDefinition(BlockTypeManager::class);
        $blockTypes = $container->findTaggedServiceIds('app.block_type');

        foreach($blockTypes as $id => $tags) {
            $definition->addMethodCall('registerBlockType', [new Reference($id)]);
        }
    }
}
