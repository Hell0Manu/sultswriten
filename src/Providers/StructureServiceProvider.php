<?php

namespace Sults\Writen\Providers;

use Sults\Writen\Contracts\ServiceProviderInterface;
use Sults\Writen\Core\Container;
use Sults\Writen\Structure\StructureManager;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;

class StructureServiceProvider implements ServiceProviderInterface {

    public function register( Container $container ): void {
        $container->set(
            StructureManager::class,
            function ( Container $c ) {
                return new StructureManager(
                    $c->get( WPUserProviderInterface::class ),
                    $c->get( AssetLoaderInterface::class ),
                    $c->get( WPPostStatusProviderInterface::class ) 
                );
            }
        );
    }
}