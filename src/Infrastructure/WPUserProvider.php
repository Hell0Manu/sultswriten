<?php
/**
 * Implementação do provedor de usuários via WordPress.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */

namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\WPUserProviderInterface;

/**
 * Classe WPUserProvider.
 *
 * Wrapper concreto para as funções de usuário do WordPress.
 * Utiliza funções globais como `wp_get_current_user`, `current_user_can` e `get_user_meta`.
 *
 * @see WPUserProviderInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */
class WPUserProvider implements WPUserProviderInterface {

	/**
	 * {@inheritDoc}
	 */
	public function get_users_dropdown( array $args ): string {
		$args['echo'] = false;
		return wp_dropdown_users( $args );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_current_user_roles(): array {
		$user = wp_get_current_user();
		return $user->roles ?? array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_current_user_id(): int {
		return get_current_user_id();
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_user_meta( int $user_id, string $key, bool $single = false ) {
		return get_user_meta( $user_id, $key, $single );
	}

	/**
	 * {@inheritDoc}
	 */
	public function update_user_meta( int $user_id, string $key, $value ) {
		return update_user_meta( $user_id, $key, $value );
	}

	/**
	 * {@inheritDoc}
	 */
	public function current_user_can( string $capability, ...$args ): bool {
		return current_user_can( $capability, ...$args );
	}
}
