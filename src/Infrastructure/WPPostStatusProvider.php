<?php
/**
 * Implementação do provedor de status usando funções do WordPress.
 *
 * Wrapper concreto para register_post_status e funções de busca de status.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */

namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\WPPostStatusProviderInterface;

class WPPostStatusProvider implements WPPostStatusProviderInterface {
	public function register( string $post_type, array $args ): object {
		return register_post_status( $post_type, $args );
	}

	public function get_status( int $post_id ): string {
		$status = get_post_status( $post_id );
		return $status ? $status : '';
	}

	public function get_status_object( string $slug ): ?object {
		return get_post_status_object( $slug );
	}

	public function get_all_status_slugs(): array {
		$stati = get_post_stati( array( 'internal' => false ), 'names' );
		return array_values( $stati );
	}
}
