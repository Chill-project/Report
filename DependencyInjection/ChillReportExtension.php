<?php

namespace Chill\ReportBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Chill\MainBundle\DependencyInjection\MissingBundleException;

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
     * 
     * @param ContainerBuilder $container
     */
    public function declareReportAsCustomizable(ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');
        if (!isset($bundles['ChillCustomFieldsBundle'])) {
            throw new MissingBundleException('ChillCustomFieldsBundle');
        }

        $container->prependExtensionConfig('chill_custom_fields',
            array('customizables_entities' => 
                array(
                    array(
                       'class' => 'Chill\ReportBundle\Entity\Report', 
                       'name' => 'ReportEntity',
                       'options' => array(
                          'summary_fields' => array(
                             'form_type' => 'custom_fields_group_linked_custom_fields',
                             'form_options' => 
                                [
                                   'multiple' => true,
                                   'expanded' => false
                                ]
                          )
                       ))
                )
            )
        );
    }
    
    /**
     * declare routes from report bundle
     * 
     * @param ContainerBuilder $container
     */
    private function declareRouting(ContainerBuilder $container)
    {
         $container->prependExtensionConfig('chill_main', array(
           'routing' => array(
              'resources' => array(
                 '@ChillReportBundle/Resources/config/routing.yml'
              )
           )
        ));
    }
    
    protected function prependRoleHierarchy(ContainerBuilder $container)
    {
        $container->prependExtensionConfig('security', array(
           'role_hierarchy' => array(
              'CHILL_REPORT_UPDATE' => array('CHILL_REPORT_SEE'),
              'CHILL_REPORT_CREATE' => array('CHILL_REPORT_SEE')
           )
        ));
    }

    /**
     * {@inheritdoc}
     * 
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        $this->declareReportAsCustomizable($container);
        $this->declareRouting($container);
        $this->prependRoleHierarchy($container);
    }
    
}
