<?php
namespace Sults\Writen\Providers;

use Sults\Writen\Contracts\ServiceProviderInterface;
use Sults\Writen\Core\Container;

// Dashboard & Export.
use Sults\Writen\Interface\Dashboard\WorkspaceController;
use Sults\Writen\Interface\Dashboard\WorkspaceAssetsManager;
use Sults\Writen\Interface\Dashboard\ExportController;
use Sults\Writen\Interface\Dashboard\ExportAssetsManager;
use Sults\Writen\Interface\AdminMenuManager;
use Sults\Writen\Interface\CategoryColorManager;
use Sults\Writen\Interface\Theme\LoginTheme;
use Sults\Writen\Integrations\AIOSEO\AIOSEOCleaner;
use Sults\Writen\Interface\Editor\GutenbergManager;
use Sults\Writen\Interface\GlobalAssetsManager;
use Sults\Writen\Workflow\Export\Transformers\GridTransformer;

// Export Helpers.
use Sults\Writen\Workflow\Export\HtmlExtractor;
use Sults\Writen\Workflow\Export\ExportProcessor;
use Sults\Writen\Workflow\Export\Transformers\ImageTransformer;
use Sults\Writen\Workflow\Export\Transformers\LinkTransformer;
use Sults\Writen\Workflow\Export\Transformers\TableTransformer;
use Sults\Writen\Workflow\Export\Transformers\SultsTipTransformer;
use Sults\Writen\Workflow\Export\Transformers\BlockquoteTransformer;
use Sults\Writen\Workflow\Export\Transformers\FileBlockTransformer;

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
					$c->get( \Sults\Writen\Contracts\JspBuilderInterface::class )
				);
			}
		);

		$container->set(
			ExportController::class,
			function ( $c ) {
				return new ExportController(
					$c->get( \Sults\Writen\Contracts\PostRepositoryInterface::class ),
					$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ),
					$c->get( \Sults\Writen\Contracts\ArchiverInterface::class ),
					$c->get( ExportProcessor::class )
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
	}
}
