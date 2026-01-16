<?php

namespace Sults\Writen\Providers;

use Sults\Writen\Contracts\ServiceProviderInterface;
use Sults\Writen\Core\Container;
use Sults\Writen\Core\HookManager;
use Sults\Writen\Structure\StructureManager;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;
use Sults\Writen\Interface\CategoryColorManager;
use Sults\Writen\Workflow\WorkflowPolicy;
use Sults\Writen\Contracts\PostRepositoryInterface;

class StructureServiceProvider implements ServiceProviderInterface {

	public function register( Container $container ): void {
		$container->set(
			StructureManager::class,
			function ( Container $c ) {
				return new StructureManager(
					$c->get( WPUserProviderInterface::class ),
					$c->get( AssetLoaderInterface::class ),
					$c->get( WPPostStatusProviderInterface::class ),
					$c->get( CategoryColorManager::class ),
					$c->get( WorkflowPolicy::class ),
					$c->get( PostRepositoryInterface::class )
				);
			}
		);
	}

	public function boot( Container $container ): void {
        $hook_manager = $container->get( HookManager::class );

        $services = array(
            $container->get( \Sults\Writen\Structure\StructureManager::class ),
        );

        $hook_manager->register_services( $services );
    }
}
