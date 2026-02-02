<?php
/**
 * Define a classe principal do plugin.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Core
 * @since      0.1.0
 */

namespace Sults\Writen\Core;

use Sults\Writen\Core\Container;
use Sults\Writen\Providers\InfrastructureServiceProvider;
use Sults\Writen\Providers\WorkflowServiceProvider;
use Sults\Writen\Providers\DashboardServiceProvider;
use Sults\Writen\Providers\StructureServiceProvider;

/**
 * Classe principal que orquestra a inicialização do plugin.
 *
 * Esta classe é responsável por instanciar o Container de Injeção de Dependências (DI),
 * carregar todos os Service Providers definidos e iniciar o ciclo de vida da aplicação
 * conectando-se aos hooks do WordPress.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Core
 * @author     Sults
 * @since      0.1.0
 */
class Plugin {

/**
	 * A versão atual do plugin.
	 *
	 * Utilizada para controle de cache de scripts e estilos.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private string $version = SULTSWRITEN_VERSION;

	/**
	 * O container de injeção de dependências.
	 *
	 * Armazena as instâncias compartilhadas dos serviços do plugin.
	 *
	 * @since 0.1.0
	 * @var Container
	 */
	private Container $container;
    
	/**
	 * Lista de provedores de serviço carregados.
	 *
	 * @since 0.1.0
	 * @var \Sults\Writen\Contracts\ServiceProviderInterface[]
	 */
    private array $service_providers = array();

	/**
	 * Inicializa a instância do plugin.
	 *
	 * Cria o container e carrega imediatamente os provedores de serviço
	 * para registrar as classes no sistema de DI.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->container = new Container();
		$this->load_providers();
	}

	/**
	 * Instancia e registra os Service Providers no container.
	 *
	 * Cada provedor é responsável por registrar suas próprias dependências
	 * dentro do Container através do método `register`.
	 *
	 * @since 0.1.0
	 * @return void
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
	 *
	 * Este método deve ser chamado logo após a instância da classe ser criada.
	 * Ele agenda o método `init` para rodar no gancho `plugins_loaded` do WordPress,
	 * garantindo que o WP esteja totalmente carregado antes de iniciarmos nossos serviços.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function run(): void {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Inicializa os serviços do plugin (Boot).
	 *
	 * Percorre todos os provedores registrados e executa o método `boot` (se existir).
	 * É aqui que os `add_action` e `add_filter` específicos de cada serviço são geralmente declarados.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function init(): void {
		foreach ( $this->service_providers as $provider ) {
            if ( method_exists( $provider, 'boot' ) ) {
                $provider->boot( $this->container );
            }
        }
	}

	/**
	 * Recupera o número da versão atual do plugin.
	 *
	 * @since 0.1.0
	 * @return string A versão do plugin (ex: "1.0.0").
	 */
	public function get_version(): string {
		return $this->version;
	}
}