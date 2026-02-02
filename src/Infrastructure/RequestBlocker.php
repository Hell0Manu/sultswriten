<?php
/**
 * Implementação do provedor de requisição.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */

namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\RequestProviderInterface;

/**
 * Classe RequestBlocker.
 *
 * Responsável por inspecionar a requisição HTTP atual de forma segura.
 * Sanitiza o acesso à variável superglobal `$_SERVER` para evitar injeção de dados
 * e acoplamento direto em outras classes.
 *
 * @see RequestProviderInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @author     Sults
 * @since      0.1.0
 */
class RequestBlocker implements RequestProviderInterface {

	/**
	 * {@inheritDoc}
	 *
	 * Verifica se o método da requisição atual é POST.
	 * Utiliza `wp_unslash` e `sanitize_text_field` para garantir que o valor lido é seguro.
	 */
	public function is_post_method(): bool {
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) ) {
			return false;
		}

		// Sanitiza o método antes de comparar.
		$method = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) );
		
		return 'POST' === $method;
	}
}
