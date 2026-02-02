<?php
/**
 * Interface de Provedor de Requisição.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

/**
 * Interface RequestProviderInterface.
 *
 * Fornece métodos para inspecionar a requisição HTTP atual (GET, POST, etc.)
 * sem acessar diretamente as superglobais $_SERVER ou $_POST.
 */
interface RequestProviderInterface {
	/**
	 * Verifica se a requisição atual é do tipo POST.
	 *
	 * @since 0.1.0
	 * @return bool True se for POST, false caso contrário.
	 */
	public function is_post_method(): bool;
}