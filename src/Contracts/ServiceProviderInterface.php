<?php
/**
 * Define a interface para Provedores de Serviço.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

use Sults\Writen\Core\Container;

/**
 * Interface ServiceProviderInterface.
 *
 * Esta interface define o contrato obrigatório para todos os Service Providers do plugin.
 * Os Service Providers são os módulos da aplicação, responsáveis por
 * registrar dependências no Container de Injeção de Dependência (DI) e inicializar
 * os ganchos (hooks) do WordPress.
 *
 * O ciclo de vida de um provider consiste em dois passos executados pelo `Plugin`:
 * 1. `register()`: Vincula classes e interfaces ao container.
 * 2. `boot()`: Executa a lógica de inicialização (add_action/add_filter).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */
interface ServiceProviderInterface {
     /**
	 * Registra serviços e definições no Container.
	 *
	 * Este método é executado imediatamente quando o plugin é instanciado.
	 * Utilize este método APENAS para ligar (bind) interfaces às suas implementações ou
	 * configurar instâncias singleton no Container.
	 *
	 * IMPORTANTE: Não utilize este método para registrar hooks do WordPress (add_action),
	 * pois outros serviços necessários podem ainda não estar registrados.
	 *
	 * @since 0.1.0
	 *
	 * @param Container $container A instância do container de DI para registrar os serviços.
	 * @return void
	 */
	public function register( Container $container ): void;

     /**
	 * Inicializa o serviço e registra ganchos (hooks).
	 *
	 * Este método é executado no hook `plugins_loaded`. Neste ponto, todos os 
	 * serviços de todos os providers já foram registrados via `register()`.
	 *
	 * Utilize este método para:
	 * - Adicionar ações (`add_action`).
	 * - Adicionar filtros (`add_filter`).
	 * - Executar lógicas que dependem de outros serviços.
	 *
	 * @since 0.1.0
	 *
	 * @param Container $container A instância do container para recuperar dependências.
	 * @return void
	 */
    public function boot( Container $container ): void;
}
