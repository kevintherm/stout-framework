<?php

declare(strict_types=1);

namespace Scotch\Container;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Scotch\Config\Config;
use Scotch\Exceptions\ScotchException;
use Scotch\Support\ServiceProvider;

final class ContainerFactory
{
    /**
     * Build the dependency injection container.
     *
     * @param Config $config
     * @param array<class-string<ServiceProvider>> $providers
     * @param array<string, mixed> $definitions Additional DI definitions
     * @throws ScotchException
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
                throw new ScotchException(
                    message: "Invalid service provider: {$providerClass}. Must implement Scotch\\Support\\ServiceProvider.",
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
            throw new ScotchException(
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
