<?php

/*
 * This file is part of the MopaBootstrapBundle.
 *
 * (c) Philipp A. Mohrenweiser <phiamo@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comur\ImageBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Add a new twig.form.resources
 *
 * @author Philipp A. Mohrenweiser <phiamo@googlemail.com>
 */
class FormPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $template = "ComurImageBundle:Form:fields.html.twig";
        $resources = $container->getParameter('twig.form.resources');
        // Ensure it wasn't already added via config
        if (!in_array($template, $resources)) {
            // If fields.html.twig is found, insert ComurImageBundle right after
            // Else insert ComurImageBundle in first position
            if (false !== ($key = array_search('fields.html.twig', $resources))) {
                array_splice($resources, ++$key, 0, $template);
            } else {
                array_unshift($resources, $template);
            }

            $container->setParameter('twig.form.resources', $resources);
        }

        $template = "ComurImageBundle:Form:croppable_image_modal.html.twig";
        // Ensure it wasn't already added via config
        if (!in_array($template, $resources)) {
            // If form_div_layout.html.twig is found, insert ComurImageBundle right after
            // Else insert ComurImageBundle in first position
            if (false !== ($key = array_search('form_div_layout.html.twig', $resources))) {
                array_splice($resources, ++$key, 0, $template);
            } else {
                array_unshift($resources, $template);
            }

            $container->setParameter('twig.form.resources', $resources);
        }
        // var_dump($resources);exit;
    }
}