<?php
/**
 * Gerenciador de ganchos (Hooks).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Core
 * @since      0.1.0
 */

namespace Sults\Writen\Core;

use Sults\Writen\Contracts\HookableInterface;

/**
 * Classe HookManager.
 *
 * Responsável por registrar ganchos (actions e filters) de uma lista de serviços.
 * Esta classe é utilizada principalmente dentro dos métodos `boot()` dos ServiceProviders.
 *
 * Ela verifica se cada serviço fornecido implementa a interface `HookableInterface`.
 * Se sim, executa o método `register()` desse serviço, onde os `add_action` e
 * `add_filter` reais estão definidos.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Core
 * @author     Sults
 * @since      0.1.0
 */
class HookManager {

	/**
	 * Processa o registro de uma lista de serviços hookáveis.
	 *
	 * Percorre o array de serviços. Se o objeto implementar `HookableInterface`,
	 * seu método `register` é chamado para efetivar a inscrição nos ganchos do WordPress.
	 *
	 * @since 0.1.0
	 *
	 * @param array<object> $services Lista de instâncias de serviços a serem registrados.
	 * @return void
	 */
	public function register_services( array $services ): void {
		foreach ( $services as $service ) {
			if ( $service instanceof HookableInterface ) {
				$service->register();
			}
		}
	}
}
