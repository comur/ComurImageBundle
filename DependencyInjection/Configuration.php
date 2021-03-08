<?php

namespace Comur\ImageBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('comur_image');
        $rootNode = method_exists($treeBuilder, "getRootNode")
            ? $treeBuilder->getRootNode()
            : $treeBuilder->root('comur_image'); // BC layer for symfony/config 4.2 and older

        $rootNode
            ->children()
                ->arrayNode('config')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('cropped_image_dir')->defaultValue('cropped')->cannotBeEmpty()->end()
                        ->scalarNode('thumbs_dir')->defaultValue('thumbnail')->cannotBeEmpty()->end()
                        ->scalarNode('gallery_dir')->defaultValue('gallery')->cannotBeEmpty()->end()
                        ->scalarNode('media_lib_thumb_size')->defaultValue(150)->cannotBeEmpty()->end()
                        ->scalarNode('gallery_thumb_size')->defaultValue(150)->cannotBeEmpty()->end()
                        ->scalarNode('public_dir')->defaultValue('%kernel.project_dir%/public')->cannotBeEmpty()->end()
                        ->scalarNode('translation_domain')->defaultValue('ComurImageBundle')->cannotBeEmpty()->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
