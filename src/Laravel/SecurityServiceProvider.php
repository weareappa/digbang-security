<?php

namespace Digbang\Security\Laravel;

use Digbang\Security\Factories\ConfigurationRepositoryFactory;
use Digbang\Security\Factories\ContainerBindingRepositoryFactory;
use Digbang\Security\Factories\DefaultRepositoryFactory;
use Digbang\Security\Factories\RepositoryFactory;
use Digbang\Security\Mappings\EmailMapping;
use Digbang\Security\Mappings\NameMapping;
use Digbang\Security\Mappings\PasswordMapping;
use Digbang\Security\SecurityContext;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use LaravelDoctrine\Fluent\FluentDriver;

class SecurityServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->singleton(SecurityContext::class);
        $this->app->bind(RepositoryFactory::class, function (Container $container) {
            return
                new ContainerBindingRepositoryFactory($container,
                    new ConfigurationRepositoryFactory($container,
                        new DefaultRepositoryFactory($container)
                    )
                );
        });
    }

    /**
     * Boot the service provider.
     *
     * @param SecurityContext $securityContext
     * @param Router $router
     * @param EntityManagerInterface $entityManager
     *
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    public function boot(SecurityContext $securityContext, Router $router, EntityManagerInterface $entityManager)
    {
        $this->addMappings($securityContext->getOrCreateFluentDriver($entityManager));
        $this->addMiddleware($router);
    }

    /**
     * @param FluentDriver $mappingDriver
     *
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    private function addMappings(FluentDriver $mappingDriver)
    {
        $mappingDriver->addMapping(new NameMapping);
        $mappingDriver->addMapping(new EmailMapping);
        $mappingDriver->addMapping(new PasswordMapping);
    }

    /**
     * @param Router $router
     */
    private function addMiddleware(Router $router)
    {
        if (method_exists($router, 'aliasMiddleware')) {
            // Laravel >= 5.4
            $router->aliasMiddleware('security', Middleware\SecurityMiddleware::class);
        } else {
            // Laravel <= 5.3
            $router->middleware('security', Middleware\SecurityMiddleware::class);
        }
    }
}
