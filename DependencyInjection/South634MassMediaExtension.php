<?php

namespace South634\MassMediaBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class South634MassMediaExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
                $container, new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yml');

        $config = $this->processConfiguration(
                new Configuration($container->getParameter('kernel.root_dir')), $configs
        );

        if (isset($config['settings'])) {
            $container->setParameter('south634_mass_media_settings', $config['settings']);
        }
    }

}
