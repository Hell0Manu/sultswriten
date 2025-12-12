<?php
namespace Sults\Writen\Core;

use Sults\Writen\Core\Container;
use Sults\Writen\Providers\InfrastructureServiceProvider;
use Sults\Writen\Providers\WorkflowServiceProvider;
use Sults\Writen\Providers\DashboardServiceProvider;

/**
 * Classe principal que comanda o plugin Sults Writen.
 */
class Plugin {

	private string $version = SULTSWRITEN_VERSION;
	private Container $container;

	public function __construct() {
		$this->container = new Container();
		$this->register_services();
	}

	/**
	 * Carrega os Service Providers.
	 */
	private function register_services(): void {
		$providers = array(
			new InfrastructureServiceProvider(),
			new WorkflowServiceProvider(),
			new DashboardServiceProvider(),
		);

		foreach ( $providers as $provider ) {
			$provider->register( $this->container );
		}
	}

	/**
	 * Ponto de entrada externo do plugin.
	 */
	public function run(): void {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Inicializa os hooks do WP após todos os plugins carregarem.
	 */
	public function init(): void {
		$hook_manager = $this->container->get( HookManager::class );

		// Serviços Globais.
		$global_services = array(
			$this->container->get( \Sults\Writen\Interface\Theme\LoginTheme::class ),
			$this->container->get( \Sults\Writen\Workflow\StatusManager::class ),
			$this->container->get( \Sults\Writen\Workflow\Media\MediaUploadManager::class ),
			$this->container->get( \Sults\Writen\Workflow\Media\ThumbnailDisabler::class ),
			$this->container->get( \Sults\Writen\Infrastructure\FeatureDisabler::class ),
		);

		$hook_manager->register_services( $global_services );

		// Serviços apenas do Admin.
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

	public function get_version(): string {
		return $this->version;
	}
}
