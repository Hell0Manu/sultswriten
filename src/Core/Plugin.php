<?php
namespace Sults\Writen\Core;

use Sults\Writen\Core\Container;
use Sults\Writen\Workflow\StatusManager;

use Sults\Writen\Workflow\Permissions\RoleManager;
use Sults\Writen\Workflow\Permissions\RoleLabelUpdater;
use Sults\Writen\Workflow\Permissions\MediaLibraryLimiter;
use Sults\Writen\Workflow\Permissions\PostListVisibility;
use Sults\Writen\Workflow\Permissions\DeletePrevention;
use Sults\Writen\Workflow\Permissions\PostRedirectionManager;

use Sults\Writen\Workflow\PostStatus\PostStatusRegistrar;
use Sults\Writen\Workflow\PostStatus\AdminAssetsManager;
use Sults\Writen\Workflow\PostStatus\PostListPresenter;

use Sults\Writen\Integrations\AIOSEO\AIOSEOCleaner;
use Sults\Writen\Interface\Theme\LoginTheme;

use Sults\Writen\Interface\Dashboard\WorkspaceController;
use Sults\Writen\Interface\Dashboard\WorkspaceAssetsManager;
use Sults\Writen\Interface\AdminMenuManager;

use Sults\Writen\Workflow\Notifications\NotificationManager;
use Sults\Writen\Contracts\NotificationRepositoryInterface;
use Sults\Writen\Infrastructure\WPNotificationRepository;

use Sults\Writen\Infrastructure\Media\GDWebPProcessor;
use Sults\Writen\Contracts\ImageProcessorInterface;
use Sults\Writen\Workflow\Media\ThumbnailDisabler;

use Sults\Writen\Contracts\HookableInterface;

use Sults\Writen\Contracts\ContentSanitizerInterface;
use Sults\Writen\Contracts\HtmlExtractorInterface;
use Sults\Writen\Workflow\Export\HtmlExtractor;

use Sults\Writen\Workflow\Export\Transformers\ImageTransformer;
use Sults\Writen\Workflow\Export\Transformers\TableTransformer;
use Sults\Writen\Workflow\Export\Transformers\SultsTipTransformer;
use Sults\Writen\Workflow\Export\Transformers\BlockquoteTransformer;
use Sults\Writen\Workflow\Export\Transformers\FileBlockTransformer;
use Sults\Writen\Workflow\Export\Transformers\LinkTransformer;

use Sults\Writen\Contracts\ConfigProviderInterface;
use Sults\Writen\Workflow\WorkflowPolicy;

/**
 * Classe principal que comanda o plugin Sults Writen.
 */
class Plugin {

	/**
	 * Versão atual do plugin.
	 *
	 * @var string
	 */
	private string $version = '0.1.0';

	/**
	 * Container de serviços do plugin.
	 *
	 * @var Container
	 */
	private Container $container;

	public function __construct() {
		$this->container = new Container();
		$this->register_services();
	}

	/**
	 * Regista as factories dos serviços no Container.
	 * Aqui é o ÚNICO lugar onde usamos constantes globais e "new Class".
	 */
	private function register_services(): void {

		$this->container->set(
			HookManager::class,
			function () {
				return new HookManager(); }
		);

		$this->container->set(
			\Sults\Writen\Contracts\WPUserProviderInterface::class,
			function () {
				return new \Sults\Writen\Infrastructure\WPUserProvider();
			}
		);

		$this->container->set(
			\Sults\Writen\Contracts\AssetLoaderInterface::class,
			function () {
				return new \Sults\Writen\Infrastructure\WPAssetLoader();
			}
		);

		$this->container->set(
			\Sults\Writen\Contracts\WPPostStatusProviderInterface::class,
			function () {
				return new \Sults\Writen\Infrastructure\WPPostStatusProvider();
			}
		);

		$this->container->set(
			PostStatusRegistrar::class,
			function ( $c ) {
				return new PostStatusRegistrar(
					$c->get( \Sults\Writen\Contracts\WPPostStatusProviderInterface::class )
				);
			}
		);

		$this->container->set(
			AdminAssetsManager::class,
			function ( $c ) {
				return new AdminAssetsManager(
					$c->get( \Sults\Writen\Contracts\AssetLoaderInterface::class ),
					$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ),
					$c->get( \Sults\Writen\Infrastructure\AssetPathResolver::class )
				);
			}
		);

		$this->container->set(
			PostListPresenter::class,
			function ( $c ) {
				return new PostListPresenter(
					$c->get( \Sults\Writen\Contracts\WPPostStatusProviderInterface::class ),
					$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class )
				);
			}
		);

		$this->container->set(
			RoleLabelUpdater::class,
			function () {
				return new RoleLabelUpdater();
			}
		);

		$this->container->set(
			MediaLibraryLimiter::class,
			function ( $c ) {
				return new MediaLibraryLimiter(
					$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class )
				);
			}
		);

		$this->container->set(
			PostListVisibility::class,
			function ( $c ) {
				return new PostListVisibility(
					$c->get( \Sults\Writen\Workflow\Permissions\VisibilityPolicy::class )
				);
			}
		);

		$this->container->set(
			DeletePrevention::class,
			function () {
				return new DeletePrevention();
			}
		);

		$this->container->set(
			RoleManager::class,
			function ( $c ) {
				return new RoleManager(
					$c->get( RoleLabelUpdater::class ),
					$c->get( MediaLibraryLimiter::class ),
					$c->get( PostListVisibility::class ),
					$c->get( DeletePrevention::class )
				);
			}
		);

		$this->container->set(
			\Sults\Writen\Workflow\Permissions\PostEditingBlocker::class,
			function ( $c ) {
				return new \Sults\Writen\Workflow\Permissions\PostEditingBlocker(
					$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ),
					$c->get( \Sults\Writen\Contracts\WPPostStatusProviderInterface::class ),
					$c->get( \Sults\Writen\Infrastructure\RequestBlocker::class ),
					$c->get( \Sults\Writen\Workflow\WorkflowPolicy::class )
				);
			}
		);

		$this->container->set(
			AIOSEOCleaner::class,
			function ( $c ) {
				return new AIOSEOCleaner(
					$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class )
				);
			}
		);

		$this->container->set(
			NotificationRepositoryInterface::class,
			function () {
				return new WPNotificationRepository();
			}
		);

		$this->container->set(
			NotificationManager::class,
			function ( $c ) {
				return new NotificationManager(
					$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ),
					$c->get( \Sults\Writen\Contracts\WPPostStatusProviderInterface::class ),
					$c->get( NotificationRepositoryInterface::class )
				);
			}
		);

		$this->container->set(
			\Sults\Writen\Contracts\PostRepositoryInterface::class,
			function ( $c ) {
				return new \Sults\Writen\Infrastructure\WPPostRepository(
					$c->get( \Sults\Writen\Workflow\Permissions\VisibilityPolicy::class )
				);
			}
		);

		$this->container->set(
			WorkspaceAssetsManager::class,
			function ( $c ) {
				return new WorkspaceAssetsManager(
					$c->get( \Sults\Writen\Contracts\AssetLoaderInterface::class ),
					$c->get( \Sults\Writen\Infrastructure\AssetPathResolver::class )
				);
			}
		);

		$this->container->set(
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

		$this->container->set(
			\Sults\Writen\Workflow\Permissions\PostRedirectionManager::class,
			function ( $c ) {
				return new \Sults\Writen\Workflow\Permissions\PostRedirectionManager(
					$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ),
					$c->get( \Sults\Writen\Contracts\WPPostStatusProviderInterface::class )
				);
			}
		);

		$this->container->set(
			LoginTheme::class,
			function ( $c ) {
				return new LoginTheme(
					$c->get( \Sults\Writen\Contracts\AssetLoaderInterface::class ),
					$c->get( \Sults\Writen\Infrastructure\AssetPathResolver::class )
				);
			}
		);

		$this->container->set(
			AdminMenuManager::class,
			function ( $c ) {
				return new AdminMenuManager(
					$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class )
				);
			}
		);

		$this->container->set(
			\Sults\Writen\Workflow\Media\MediaUploadManager::class,
			function ( $c ) {
				return new \Sults\Writen\Workflow\Media\MediaUploadManager(
					$c->get( \Sults\Writen\Contracts\ImageProcessorInterface::class )
				);
			}
		);

		$this->container->set(
			\Sults\Writen\Workflow\Media\ThumbnailDisabler::class,
			function () {
				return new \Sults\Writen\Workflow\Media\ThumbnailDisabler();
			}
		);

		$this->container->set(
			\Sults\Writen\Contracts\ImageProcessorInterface::class,
			function () {
				return new \Sults\Writen\Infrastructure\Media\GDWebPProcessor();
			}
		);

		$this->container->set(
			\Sults\Writen\Infrastructure\RequestBlocker::class,
			function () {
				return new \Sults\Writen\Infrastructure\RequestBlocker();
			}
		);

		$this->container->set(
			\Sults\Writen\Interface\CategoryColorManager::class,
			function ( $c ) {
				return new \Sults\Writen\Interface\CategoryColorManager(
					$c->get( \Sults\Writen\Contracts\AssetLoaderInterface::class )
				);
			}
		);

		$this->container->set(
			\Sults\Writen\Interface\Dashboard\ExportController::class,
			function ( $c ) {
				return new \Sults\Writen\Interface\Dashboard\ExportController(
					$c->get( \Sults\Writen\Contracts\PostRepositoryInterface::class ),
					$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ),
					$c->get( \Sults\Writen\Contracts\HtmlExtractorInterface::class )
				);
			}
		);

		$this->container->set(
			\Sults\Writen\Interface\Dashboard\ExportAssetsManager::class,
			function ( $c ) {
				return new \Sults\Writen\Interface\Dashboard\ExportAssetsManager(
					$c->get( \Sults\Writen\Contracts\AssetLoaderInterface::class ),
					$c->get( \Sults\Writen\Infrastructure\AssetPathResolver::class )
				);
			}
		);

		$this->container->set(
			\Sults\Writen\Workflow\Permissions\VisibilityPolicy::class,
			function ( $c ) {
				return new \Sults\Writen\Workflow\Permissions\VisibilityPolicy(
					$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class )
				);
			}
		);

		$this->container->set(
			\Sults\Writen\Contracts\ConfigProviderInterface::class,
			function () {
				return new \Sults\Writen\Infrastructure\WPConfigProvider();
			}
		);

		$this->container->set(
            \Sults\Writen\Workflow\WorkflowPolicy::class,
            function () {
                return new \Sults\Writen\Workflow\WorkflowPolicy();
            }
        );

		$this->container->set(
			\Sults\Writen\Infrastructure\AssetPathResolver::class,
			function () {
				return new \Sults\Writen\Infrastructure\AssetPathResolver(
					SULTSWRITEN_URL,
					SULTSWRITEN_VERSION
				);
			}
		);

		$this->container->set(
			\Sults\Writen\Contracts\HtmlExtractorInterface::class,
			function ( $c ) {
				$attachment_provider = $c->get( \Sults\Writen\Contracts\AttachmentProviderInterface::class );
				$config_provider     = $c->get( \Sults\Writen\Contracts\ConfigProviderInterface::class ); // <--- Pega o config

				$transformers = array(
					new ImageTransformer( $attachment_provider, $config_provider ),
					new LinkTransformer( $config_provider ),
					new TableTransformer(),
					new SultsTipTransformer( $config_provider ),
					new BlockquoteTransformer(),
					new FileBlockTransformer( $attachment_provider, $config_provider ),
				);
				return new HtmlExtractor( $transformers, $config_provider );
			}
		);

		$this->container->set(
			\Sults\Writen\Contracts\ContentSanitizerInterface::class,
			function () {
				return new HtmlSanitizer();
			}
		);

		$this->container->set(
			\Sults\Writen\Contracts\AttachmentProviderInterface::class,
			function () {
				return new \Sults\Writen\Infrastructure\WPAttachmentProvider();
			}
		);

		$this->container->set(
			\Sults\Writen\Infrastructure\FeatureDisabler::class,
			function () {
				return new \Sults\Writen\Infrastructure\FeatureDisabler();
			}
		);

		$this->container->set(
			StatusManager::class,
			function ( $c ) {
				return new StatusManager(
					$c->get( PostStatusRegistrar::class ),
					$c->get( AdminAssetsManager::class ),
					$c->get( PostListPresenter::class ),
					$c->get( \Sults\Writen\Workflow\Permissions\PostEditingBlocker::class ),
					$c->get( RoleManager::class ),
					$c->get( NotificationManager::class ),
					$c->get( \Sults\Writen\Workflow\Permissions\PostRedirectionManager::class )
				);
			}
		);
	}

	/**
	 * Registra os hooks principais do plugin.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Inicialização pública (frontend + geral).
	 *
	 * @return void
	 */
	public function init(): void {
		$hook_manager = $this->container->get( HookManager::class );

		$global_services = array(
			$this->container->get( \Sults\Writen\Interface\Theme\LoginTheme::class ),
			$this->container->get( \Sults\Writen\Workflow\StatusManager::class ),
			$this->container->get( \Sults\Writen\Workflow\Media\MediaUploadManager::class ),
			$this->container->get( \Sults\Writen\Workflow\Media\ThumbnailDisabler::class ),
			$this->container->get( \Sults\Writen\Infrastructure\FeatureDisabler::class ),
		);

		$hook_manager->register_services( $global_services );

		if ( is_admin() ) {
			$admin_services = array();

			if ( defined( 'AIOSEO_VERSION' ) ) {
				$admin_services[] = $this->container->get( \Sults\Writen\Integrations\AIOSEO\AIOSEOCleaner::class );
			}

			$admin_services[] = $this->container->get( \Sults\Writen\Interface\Dashboard\WorkspaceController::class );
			$admin_services[] = $this->container->get( \Sults\Writen\Interface\Dashboard\WorkspaceAssetsManager::class );
			$admin_services[] = $this->container->get( \Sults\Writen\Interface\AdminMenuManager::class );
			$admin_services[] = $this->container->get( \Sults\Writen\Interface\CategoryColorManager::class );
			$admin_services[] = $this->container->get( \Sults\Writen\Interface\Dashboard\ExportController::class );
			$admin_services[] = $this->container->get( \Sults\Writen\Interface\Dashboard\ExportAssetsManager::class );

			$hook_manager->register_services( $admin_services );
		}
	}

	/**
	 * Ponto de entrada externo do plugin.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->init_hooks();
	}

	/**
	 * Retorna a versão atual do plugin.
	 *
	 * @return string
	 */
	public function get_version(): string {
		return $this->version;
	}
}