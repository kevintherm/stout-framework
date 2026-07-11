<?php

declare(strict_types=1);

namespace Stout\Container;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Stout\Config\Config;
use Stout\Exceptions\StoutException;
use Stout\Support\ServiceProvider;

final class ContainerFactory
{
    /**
     * Build the dependency injection container.
     *
     * @param Config $config
     * @param array<class-string<ServiceProvider>> $providers
     * @param array<string, mixed> $definitions Additional DI definitions
     * @throws StoutException
     */
    public static function build(Config $config, array $providers = [], array $definitions = []): ContainerInterface
    {
        $builder = new ContainerBuilder();

        $builder->addDefinitions(array_merge([
            Config::class => $config,
        ], $definitions));

        /** @var ServiceProvider[] $instantiatedProviders */
        $instantiatedProviders = [];

        foreach ($providers as $providerClass) {
            if (!class_exists($providerClass) || !is_subclass_of($providerClass, ServiceProvider::class)) {
                throw new StoutException(
                    message: "Invalid service provider: {$providerClass}. Must implement Stout\\Support\\ServiceProvider.",
                    context: ['provider' => $providerClass]
                );
            }
            
            $provider = new $providerClass();
            $provider->register($builder);
            $instantiatedProviders[] = $provider;
        }

        try {
            $container = $builder->build();
        } catch (\Exception $e) {
            throw new StoutException(
                message: "Failed to compile the dependency injection container.",
                previous: $e
            );
        }

        foreach ($instantiatedProviders as $provider) {
            $provider->boot($container);
        }

        return $container;
    }
}
