<?php

namespace Dnd\Bundle\CriteoConnectorBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Class DndCriteoConnectorExtension
 *
 * @author          Didier Youn <didier.youn@dnd.fr>
 * @copyright       Copyright (c) 2019 Agence Dn'D
 * @license         https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link            https://www.dnd.fr/
 */
class DndCriteoConnectorExtension extends Extension
{

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('controllers.yml');
        $loader->load('job_constraints.yml');
        $loader->load('job_defaults.yml');
        $loader->load('jobs.yml');
        $loader->load('providers.yml');
        $loader->load('steps.yml');
        $loader->load('writers.yml');
        $loader->load('array_converters.yml');
        $loader->load('renderers.yml');
    }
}
