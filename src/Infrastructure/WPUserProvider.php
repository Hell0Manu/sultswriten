<?php
/**
 * Classe WPUserProvider
 * * Implementação concreta que usa as funções nativas do WordPress.
 * Esta classe é usada quando o plugin está rodando no site real.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */

namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\WPUserProviderInterface;

class WPUserProvider implements WPUserProviderInterface {
	public function get_users_dropdown( array $args ): string {
		$args['echo'] = false;
		return wp_dropdown_users( $args );
	}

	public function get_current_user_roles(): array {
		$user = wp_get_current_user();
		return $user->roles ?? array();
	}

	public function get_current_user_id(): int {
		return get_current_user_id();
	}

	public function get_user_meta( int $user_id, string $key, bool $single = false ) {
		return get_user_meta( $user_id, $key, $single );
	}

	public function update_user_meta( int $user_id, string $key, $value ) {
		return update_user_meta( $user_id, $key, $value );
	}
}
