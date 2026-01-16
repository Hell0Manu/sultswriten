<?php
namespace Sults\Writen\Core;

use Sults\Writen\Core\Container;
use Sults\Writen\Providers\InfrastructureServiceProvider;
use Sults\Writen\Providers\WorkflowServiceProvider;
use Sults\Writen\Providers\DashboardServiceProvider;
use Sults\Writen\Providers\StructureServiceProvider;

/**
 * Classe principal que comanda o plugin.
 */
class Plugin {

	private string $version = SULTSWRITEN_VERSION;
	private Container $container;
    
    /**
     * @var \Sults\Writen\Contracts\ServiceProviderInterface[]
     */
    private array $service_providers = array();

	public function __construct() {
		$this->container = new Container();
		$this->load_providers();
	}

	/**
	 * Instancia e registra os Service Providers no container.
	 */
	private function load_providers(): void {
		$this->service_providers = array(
			new InfrastructureServiceProvider(),
			new WorkflowServiceProvider(),
			new DashboardServiceProvider(),
			new StructureServiceProvider(),
		);

		foreach ( $this->service_providers as $provider ) {
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
	 * Inicializa os hooks delegando para os providers.
	 */
	public function init(): void {
		foreach ( $this->service_providers as $provider ) {
            if ( method_exists( $provider, 'boot' ) ) {
                $provider->boot( $this->container );
            }
        }
	}

	public function get_version(): string {
		return $this->version;
	}
}