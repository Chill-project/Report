<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        return array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Chill\ReportBundle\ChillReportBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Chill\CustomFieldsBundle\ChillCustomFieldsBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Chill\MainBundle\ChillMainBundle(),
            new Chill\PersonBundle\ChillPersonBundle(),
            new \Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle()
        );   
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }

    /**
     * @return string
     */
    public function getCacheDir()
    {
        return sys_get_temp_dir().'/ChillReportBundle/cache';
    }

    /**
     * @return string
     */
    public function getLogDir()
    {
        return sys_get_temp_dir().'/ChillReportBundle/logs';
    }
}
