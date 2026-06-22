<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * @return list<string> An array of allowed values for APP_ENV
     */
    private function getAllowedEnvs(): array
    {
        return ['prod', 'dev', 'test'];
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $confDir = $this->getProjectDir().'/config';

        $container->import($confDir.'/packages/*.yaml');
        if (is_dir($confDir.'/packages/'.$this->environment)) {
            $container->import($confDir.'/packages/'.$this->environment.'/*.yaml');
        }

        $container->import($confDir.'/services.yaml');
        $envServices = $confDir.'/services_'.$this->environment.'.yaml';
        if (file_exists($envServices)) {
            $container->import($envServices);
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $confDir = $this->getProjectDir().'/config';

        $routes->import($confDir.'/routes/*.yaml');
        if (is_dir($confDir.'/routes/'.$this->environment)) {
            $routes->import($confDir.'/routes/'.$this->environment.'/*.yaml');
        }

        $routes->import($confDir.'/routes.yaml');
    }
}
