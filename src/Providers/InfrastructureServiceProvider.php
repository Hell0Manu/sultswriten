<?php
namespace Sults\Writen\Providers;

use Sults\Writen\Contracts\ServiceProviderInterface;
use Sults\Writen\Core\Container;
use Sults\Writen\Core\HookManager;
use Sults\Writen\Infrastructure\WPUserProvider;
use Sults\Writen\Infrastructure\WPAssetLoader;
use Sults\Writen\Infrastructure\WPPostStatusProvider;
use Sults\Writen\Infrastructure\WPNotificationRepository;
use Sults\Writen\Infrastructure\WPPostRepository;
use Sults\Writen\Infrastructure\WPAttachmentProvider;
use Sults\Writen\Infrastructure\WPConfigProvider;
use Sults\Writen\Infrastructure\RequestBlocker;
use Sults\Writen\Infrastructure\AssetPathResolver;
use Sults\Writen\Infrastructure\FeatureDisabler;
use Sults\Writen\Infrastructure\PostConfigurator;
use Sults\Writen\Infrastructure\HomeRedirector;
use Sults\Writen\Infrastructure\NotFoundRedirector;
use Sults\Writen\Infrastructure\WPFileSystem;

class InfrastructureServiceProvider implements ServiceProviderInterface {

	public function register( Container $container ): void {

		// Gerenciador de Hooks.
		$container->set( HookManager::class, fn() => new HookManager() );

		// Provedores de Dados do WP (Wrappers).
		$container->set( \Sults\Writen\Contracts\WPUserProviderInterface::class, fn() => new WPUserProvider() );
		$container->set( \Sults\Writen\Contracts\AssetLoaderInterface::class, fn() => new WPAssetLoader() );
		$container->set( \Sults\Writen\Contracts\WPPostStatusProviderInterface::class, fn() => new WPPostStatusProvider() );
		$container->set( \Sults\Writen\Contracts\NotificationRepositoryInterface::class, fn() => new WPNotificationRepository() );
		$container->set( \Sults\Writen\Contracts\AttachmentProviderInterface::class, fn() => new WPAttachmentProvider() );

		$container->set(
			\Sults\Writen\Contracts\FileSystemInterface::class,
			function () {
				return new WPFileSystem();
			}
		);

		// Configuração e Requests.
		$container->set( \Sults\Writen\Contracts\ConfigProviderInterface::class, fn() => new WPConfigProvider() );
		$container->set( \Sults\Writen\Contracts\RequestProviderInterface::class, fn() => new RequestBlocker() );

		// Redirectors.
		$container->set( HomeRedirector::class, fn() => new HomeRedirector() );
		$container->set( NotFoundRedirector::class, fn() => new NotFoundRedirector() );

		// Repositório de Posts.
		$container->set(
			\Sults\Writen\Contracts\PostRepositoryInterface::class,
			function ( $c ) {
				return new WPPostRepository(
					$c->get( \Sults\Writen\Workflow\Permissions\VisibilityPolicy::class )
				);
			}
		);

		// Utilitários.
		$container->set( FeatureDisabler::class, fn() => new FeatureDisabler() );

		$container->set(
			AssetPathResolver::class,
			function () {
				return new AssetPathResolver( SULTSWRITEN_URL, SULTSWRITEN_VERSION );
			}
		);

		// Zip Archiver.
		$container->set(
			\Sults\Writen\Contracts\ArchiverInterface::class,
			function () {
				return new \Sults\Writen\Infrastructure\ZipArchiver();
			}
		);

		$container->set(
			PostConfigurator::class,
			fn() => new PostConfigurator()
		);
	}

	public function boot( Container $container ): void {
        $hook_manager = $container->get( HookManager::class );

		$services = array(
            $container->get( \Sults\Writen\Infrastructure\FeatureDisabler::class ),
            $container->get( \Sults\Writen\Infrastructure\PostConfigurator::class ),
            $container->get( \Sults\Writen\Infrastructure\HomeRedirector::class ),
            $container->get( \Sults\Writen\Infrastructure\NotFoundRedirector::class ),
        );

        $hook_manager->register_services( $services );
    }

}
