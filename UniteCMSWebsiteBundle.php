<?php


namespace Unite\CMSWebsiteBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Unite\CMSWebsiteBundle\BlockTypes\BlockTypeInterface;
use Unite\CMSWebsiteBundle\DependencyInjection\BlockTypePass;

class UniteCMSWebsiteBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container
            ->registerForAutoconfiguration(BlockTypeInterface::class)
            ->addTag('app.block_type');
        $container->addCompilerPass(new BlockTypePass());
    }
}
