<?php
/**
 * Implementação do provedor de status usando funções do WordPress.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */

namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\WPPostStatusProviderInterface;

/**
 * Classe WPPostStatusProvider.
 *
 * Wrapper concreto para as funções nativas de status do WordPress.
 * Permite registrar novos status (como 'em-revisao', 'finished') e consultar
 * o estado atual dos posts de forma testável.
 *
 * @see WPPostStatusProviderInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @author     Sults
 * @since      0.1.0
 */
class WPPostStatusProvider implements WPPostStatusProviderInterface {

	/**
	 * {@inheritDoc}
	 */
	public function register( string $sults_post_type, array $args ): object {
		return register_post_status( $sults_post_type, $args );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_status( int $sults_post_id ): string {
		$status = get_post_status( $sults_post_id );
		// Retorna string vazia em vez de false se falhar, para manter consistência de tipo.
		return $status ? $status : '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_status_object( string $slug ): ?object {
		return get_post_status_object( $slug );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_all_status_slugs(): array {
		// Busca status registrados que não sejam marcados como 'internal'.
		$stati = get_post_stati( array( 'internal' => false ), 'names' );
		return array_values( $stati );
	}
}