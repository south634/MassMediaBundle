<?php

namespace South634\MassMediaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    private $root_dir;
    
    public function __construct($root_dir)
    {
        $this->root_dir = $root_dir;
    }
    
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('south634_mass_media');
        
        $rootNode
                ->children()
                    ->arrayNode('settings')
                        ->isRequired()
                        ->validate()
                        ->ifTrue(function($v) {
                            $testFileName = hash($v['hash_algo'], 'test');
                            return strlen($testFileName) < $v['folder_depth'] * $v['folder_chars'] ? true : false;
                        })
                            ->thenInvalid('folder_depth * folder_chars cannot be greater than string created by hash_algo')
                        ->end()                
                        ->children()
                            ->scalarNode('hash_algo')
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->validate()
                                ->ifNotInArray(hash_algos())
                                    ->thenInvalid('%s is not a supported hash algorithm')
                                ->end()
                            ->end()
                            ->integerNode('folder_depth')
                                ->isRequired()
                                ->min(0)
                            ->end()
                            ->integerNode('folder_chars')
                                ->isRequired()
                                ->min(0)
                            ->end()
                            ->scalarNode('upload_dir')
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->info('Set name of directory to upload all media to')
                            ->end()
                            ->scalarNode('web_dir_name')
                                ->defaultValue('web')
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('root_dir')
                                ->defaultValue($this->root_dir)
                                ->cannotBeEmpty()
                            ->end()
                        ->end()
                    ->end() // settings
                ->end()
        ;
        
        return $treeBuilder;
    }
}