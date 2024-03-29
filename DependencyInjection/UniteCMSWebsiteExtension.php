<?php


namespace Unite\CMSWebsiteBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Unite\CMSWebsiteBundle\EventSubscriber\RequestSiteInjectorListener;
use Unite\CMSWebsiteBundle\Services\SiteManager;

class UniteCMSWebsiteExtension extends Extension
{

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $definition = $container->getDefinition(SiteManager::class);
        $definition->setArgument('$queryParts', $config['site_manager_query']);

        $definition = $container->getDefinition(RequestSiteInjectorListener::class);
        $definition->setArgument('$multiLanguage', (bool)$config['multilanguage']);
    }
}
