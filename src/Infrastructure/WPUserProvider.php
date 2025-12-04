<?php
/**
 * Implementação do provedor de usuários usando funções do WordPress.
 *
 * Wrapper concreto para wp_dropdown_users e wp_get_current_user.
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
}