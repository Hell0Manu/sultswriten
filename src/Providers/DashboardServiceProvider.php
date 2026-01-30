<?php
namespace Sults\Writen\Providers;

use Sults\Writen\Contracts\ServiceProviderInterface;
use Sults\Writen\Core\Container;
use Sults\Writen\Core\HookManager;

// Dashboard & Export.
use Sults\Writen\Interface\Dashboard\WorkspaceController;
use Sults\Writen\Interface\Dashboard\WorkspaceAssetsManager;
use Sults\Writen\Interface\Dashboard\ExportController;
use Sults\Writen\Interface\Dashboard\ExportAssetsManager;
use Sults\Writen\Infrastructure\SimpleViewRenderer;
use Sults\Writen\Interface\AdminMenuManager;
use Sults\Writen\Interface\CategoryColorManager;
use Sults\Writen\Interface\Theme\LoginTheme;
use Sults\Writen\Integrations\AIOSEO\AIOSEOCleaner;
use Sults\Writen\Interface\Editor\GutenbergManager;
use Sults\Writen\Interface\GlobalAssetsManager;
use Sults\Writen\Workflow\Export\Transformers\GridTransformer;
use Sults\Writen\Contracts\JspHtmlSanitizerInterface;
use Sults\Writen\Contracts\ExportNamingServiceInterface;

// Export Helpers.
use Sults\Writen\Workflow\Export\HtmlExtractor;
use Sults\Writen\Workflow\Export\ExportProcessor;
use Sults\Writen\Workflow\Export\Transformers\ImageTransformer;
use Sults\Writen\Workflow\Export\Transformers\LinkTransformer;
use Sults\Writen\Workflow\Export\Transformers\TableTransformer;
use Sults\Writen\Workflow\Export\Transformers\SultsTipTransformer;
use Sults\Writen\Workflow\Export\Transformers\BlockquoteTransformer;
use Sults\Writen\Workflow\Export\Transformers\FileBlockTransformer;
use Sults\Writen\Workflow\Export\ExportNamingService;
use Sults\Writen\Workflow\Export\JspHtmlSanitizer;
use Sults\Writen\Workflow\Export\ExportMetadataBuilder;

use Sults\Writen\Integrations\ReactAppLoader;

class DashboardServiceProvider implements ServiceProviderInterface {

	public function register( Container $container ): void {

		// Assets do Workspace.
		$container->set(
			WorkspaceAssetsManager::class,
			function ( $c ) {
				return new WorkspaceAssetsManager(
					$c->get( \Sults\Writen\Contracts\AssetLoaderInterface::class ),
					$c->get( \Sults\Writen\Infrastructure\AssetPathResolver::class )
				);
			}
		);

		// Workspace Controller.
		$container->set(
			WorkspaceController::class,
			function ( $c ) {
				return new WorkspaceController(
					$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ),
					$c->get( \Sults\Writen\Contracts\NotificationRepositoryInterface::class ),
					$c->get( \Sults\Writen\Contracts\PostRepositoryInterface::class ),
					$c->get( \Sults\Writen\Workflow\WorkflowPolicy::class )
				);
			}
		);

		// Export Assets.
		$container->set(
			ExportAssetsManager::class,
			function ( $c ) {
				return new ExportAssetsManager(
					$c->get( \Sults\Writen\Contracts\AssetLoaderInterface::class ),
					$c->get( \Sults\Writen\Infrastructure\AssetPathResolver::class )
				);
			}
		);

		// Html Extractor.
		$container->set(
			\Sults\Writen\Contracts\HtmlExtractorInterface::class,
			function ( $c ) {
				$attachment_provider = $c->get( \Sults\Writen\Contracts\AttachmentProviderInterface::class );
				$config_provider     = $c->get( \Sults\Writen\Contracts\ConfigProviderInterface::class );

				$transformers = array(
					new ImageTransformer( $attachment_provider, $config_provider ),
					new LinkTransformer( $config_provider ),
					new GridTransformer(),
					new TableTransformer(),
					new SultsTipTransformer( $config_provider ),
					new BlockquoteTransformer(),
					new FileBlockTransformer( $attachment_provider, $config_provider ),
				);
				return new HtmlExtractor( $transformers, $config_provider );
			}
		);

		$container->set(
			ExportProcessor::class,
			function ( $c ) {
				return new ExportProcessor(
					$c->get( \Sults\Writen\Contracts\HtmlExtractorInterface::class ),
					$c->get( \Sults\Writen\Workflow\Export\ExportAssetsManager::class ),
					$c->get( \Sults\Writen\Contracts\SeoDataProviderInterface::class ),
					$c->get( \Sults\Writen\Contracts\JspBuilderInterface::class ),
					$c->get( JspHtmlSanitizerInterface::class ),
					new ExportMetadataBuilder()
				);
			}
		);

		    $container->set(
            ViewRendererInterface::class,

            function () {
                $views_path = dirname( __DIR__ ) . '/Interface/Dashboard/views/';
                return new SimpleViewRenderer( $views_path );
            }

        );

		$container->set(
			ExportController::class,
			function ( $c ) {
				return new ExportController(
					$c->get( \Sults\Writen\Contracts\PostRepositoryInterface::class ),
                    $c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ),
                    $c->get( ExportProcessor::class ),
                    $c->get( \Sults\Writen\Contracts\ConfigProviderInterface::class ),
                   $c->get( ViewRendererInterface::class )
				);
			}
		);

		$container->set(
			LoginTheme::class,
			function ( $c ) {
				return new LoginTheme(
					$c->get( \Sults\Writen\Contracts\AssetLoaderInterface::class ),
					$c->get( \Sults\Writen\Infrastructure\AssetPathResolver::class )
				);
			}
		);

		$container->set(
			AdminMenuManager::class,
			function ( $c ) {
				return new AdminMenuManager( $c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ) );
			}
		);

		$container->set(
			CategoryColorManager::class,
			function ( $c ) {
				return new CategoryColorManager( $c->get( \Sults\Writen\Contracts\AssetLoaderInterface::class ) );
			}
		);

		// Integrations.
		$container->set(
			AIOSEOCleaner::class,
			function ( $c ) {
				return new AIOSEOCleaner( $c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ) );
			}
		);

		// Integrations: AIOSEO Data Provider.
		$container->set(
			\Sults\Writen\Contracts\SeoDataProviderInterface::class,
			function () {
				return new \Sults\Writen\Integrations\AIOSEO\AioseoDataProvider();
			}
		);

		// JSP Builder.
		$container->set(
			\Sults\Writen\Contracts\JspBuilderInterface::class,
			fn() => new \Sults\Writen\Workflow\Export\JspBuilder()
		);

		// Export Assets Manager.
		$container->set(
			\Sults\Writen\Workflow\Export\ExportAssetsManager::class,
			function () {
				return new \Sults\Writen\Workflow\Export\ExportAssetsManager();
			}
		);

		$container->set(
			LinkTransformer::class,
			function ( $c ) {
				return new LinkTransformer( $c->get( \Sults\Writen\Contracts\ConfigProviderInterface::class ) );
			}
		);

		$container->set(
			ImageTransformer::class,
			function ( $c ) {
				return new ImageTransformer(
					$c->get( \Sults\Writen\Contracts\AttachmentProviderInterface::class ),
					$c->get( \Sults\Writen\Contracts\ConfigProviderInterface::class )
				);
			}
		);

		$container->set(
			GutenbergManager::class,
			function ( $c ) {
				return new GutenbergManager(
					$c->get( \Sults\Writen\Contracts\AssetLoaderInterface::class ),
					$c->get( \Sults\Writen\Infrastructure\AssetPathResolver::class )
				);
			}
		);

		$container->set(
			\Sults\Writen\Interface\GlobalAssetsManager::class,
			function ( $c ) {
				return new \Sults\Writen\Interface\GlobalAssetsManager(
					$c->get( \Sults\Writen\Contracts\AssetLoaderInterface::class ),
					$c->get( \Sults\Writen\Infrastructure\AssetPathResolver::class )
				);
			}
		);

		$container->set(
            \Sults\Writen\Interface\Dashboard\ExportDownloadHandler::class,
            function ( $c ) {
                return new \Sults\Writen\Interface\Dashboard\ExportDownloadHandler(
                    $c->get( \Sults\Writen\Contracts\ArchiverInterface::class ),
                    $c->get( ExportProcessor::class ),
                    $c->get( \Sults\Writen\Contracts\ExportNamingServiceInterface::class ),
                    $c->get( \Sults\Writen\Contracts\FileSystemInterface::class ),
					$c->get( \Sults\Writen\Contracts\ConfigProviderInterface::class )
                );
            }
        );

        $container->set(
            \Sults\Writen\Contracts\ViewRendererInterface::class,
            function () {

                $views_path = plugin_dir_path( dirname( __DIR__ ) ) . 'src/Interface/Dashboard/views/';
                
                return new \Sults\Writen\Infrastructure\SimpleViewRenderer( $views_path );
            }
        );

		$container->set( ExportNamingService::class, fn() => new ExportNamingService() );
		$container->set( JspHtmlSanitizer::class, fn() => new JspHtmlSanitizer() );
		$container->set( ExportNamingServiceInterface::class, fn() => new ExportNamingService() );
		$container->set( JspHtmlSanitizerInterface::class, fn() => new JspHtmlSanitizer() );
		$container->set(
            ReactAppLoader::class,
            function () {
                return new ReactAppLoader();
            }
        );
    }

	public function boot( Container $container ): void {
        $hook_manager = $container->get( HookManager::class );

        $global_services = array(
            $container->get( \Sults\Writen\Interface\Theme\LoginTheme::class ),
            $container->get( \Sults\Writen\Interface\Editor\GutenbergManager::class ),
            $container->get( \Sults\Writen\Interface\GlobalAssetsManager::class ),
        );
        $hook_manager->register_services( $global_services );

        if ( is_admin() ) {
            $admin_services = array();

            if ( defined( 'AIOSEO_VERSION' ) ) {
                $admin_services[] = $container->get( \Sults\Writen\Integrations\AIOSEO\AIOSEOCleaner::class );
            }

            $admin_services[] = $container->get( \Sults\Writen\Interface\Dashboard\WorkspaceController::class );
            $admin_services[] = $container->get( \Sults\Writen\Interface\Dashboard\WorkspaceAssetsManager::class );
            $admin_services[] = $container->get( \Sults\Writen\Interface\AdminMenuManager::class );
            $admin_services[] = $container->get( \Sults\Writen\Interface\CategoryColorManager::class );
            
            $admin_services[] = $container->get( \Sults\Writen\Interface\Dashboard\ExportController::class );
			$admin_services[] = $container->get( \Sults\Writen\Interface\Dashboard\ExportDownloadHandler::class ); 
            $admin_services[] = $container->get( \Sults\Writen\Interface\Dashboard\ExportAssetsManager::class );

			$admin_services[] = $container->get( ReactAppLoader::class );

            $hook_manager->register_services( $admin_services );
        }
    }
}
