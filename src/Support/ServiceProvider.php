<?php

declare(strict_types=1);

namespace Scotch\Support;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

/**
 * Contract for Scotch service providers.
 *
 * Providers are the canonical way to bind services into the container and
 * run any bootstrapping that requires a fully built container.
 */
interface ServiceProvider
{
    /**
     * Register bindings into the DI container builder.
     * Called before the container is compiled.
     *
     * @param ContainerBuilder<\DI\Container> $builder
     */
    public function register(ContainerBuilder $builder): void;

    /**
     * Bootstrap services that require a fully built container.
     * Called after the container is compiled.
     */
    public function boot(ContainerInterface $container): void;
}
