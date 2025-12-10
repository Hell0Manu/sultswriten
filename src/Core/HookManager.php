<?php
/**
 * Processa o registro de uma lista de serviços hookáveis.
 *
 * @param array $services Lista de instâncias ou identificadores de serviços.
 */
namespace Sults\Writen\Core;

use Sults\Writen\Contracts\HookableInterface;

class HookManager {

	public function register_services( array $services ): void {
		foreach ( $services as $service ) {
			if ( $service instanceof HookableInterface ) {
				$service->register();
			}
		}
	}
}
