<?php
namespace Sults\Writen\Core;

use Sults\Writen\Core\Container;
use Sults\Writen\Workflow\StatusManager;
use Sults\Writen\Workflow\PostStatus\PostStatusRegistrar;
use Sults\Writen\Workflow\PostStatus\AdminAssetsManager;
use Sults\Writen\Workflow\PostStatus\PostListPresenter;


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
		$this->define_constants();
		$this->register_services();
		// $this->init_hooks();
	}

	/**
	 * Regista as factories dos serviços no Container.
	 * Aqui é o ÚNICO lugar onde usamos constantes globais e "new Class".
	 */
	private function register_services(): void {
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
					SULTSWRITEN_URL,
					SULTSWRITEN_VERSION,
					$c->get( \Sults\Writen\Contracts\AssetLoaderInterface::class ),
					$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class )
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
			\Sults\Writen\Workflow\Permissions\PostEditingBlocker::class,
			function ( $c ) {
				return new \Sults\Writen\Workflow\Permissions\PostEditingBlocker(
					$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ),
					$c->get( \Sults\Writen\Contracts\WPPostStatusProviderInterface::class )
				);
			}
		);

		$this->container->set(
			\Sults\Writen\Workflow\Permissions\RoleManager::class,
			function ( $c ) {
				return new \Sults\Writen\Workflow\Permissions\RoleManager(
					$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class )
				);
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
					$c->get( \Sults\Writen\Workflow\Permissions\RoleManager::class )
				);
			}
		);
	}

	/**
	 * Define constantes globais usadas pelo plugin.
	 *
	 * @return void
	 */
	private function define_constants(): void {
		if ( ! defined( 'SULTSWRITEN_PATH' ) ) {
			define( 'SULTSWRITEN_PATH', plugin_dir_path( dirname( __DIR__ ) ) );
		}
	}

	/**
	 * Registra os hooks principais do plugin.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Inicialização pública (frontend + geral).
	 *
	 * @return void
	 */
	public function init(): void {
		$status_manager = $this->container->get( StatusManager::class );
		$status_manager->register();
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
