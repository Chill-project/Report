<?php

namespace Chill\ReportBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class ChillReportExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    /**
     * Declare the entity Report, as a customizable entity (can add custom fields)
     */
    public function declareReportAsCustomizable(ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');
        if (!isset($bundles['ChillCustomFieldsBundle'])) {
            throw new MissingBundleException('ChillCustomFieldsBundle');
        }

        $customizables_entities['customizables_entities'][] =  array('class' => 'Chill\ReportBundle\Entity\Report', 'name' => 'ReportName');

        $container->prependExtensionConfig('chill_custom_fields', $customizables_entities);
    }

    public function prepend(ContainerBuilder $container)
    {
        $this->declareReportAsCustomizable($container);
    }
}
