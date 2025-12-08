<?php
/**
 * Bloqueia a edição de posts baseada no status e na role do usuário.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Permissions;

use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;

class PostEditingBlocker {
	private WPUserProviderInterface $user_provider;
	private WPPostStatusProviderInterface $status_provider;

	private array $blocked_statuses = array( 'review_in_progress', 'suspended', 'finished' );

	private array $blocked_roles = array( 'contributor' );

	public function __construct(
		WPUserProviderInterface $user_provider,
		WPPostStatusProviderInterface $status_provider
	) {
		$this->user_provider   = $user_provider;
		$this->status_provider = $status_provider;
	}

	public function register(): void {
		add_filter( 'map_meta_cap', array( $this, 'filter_map_meta_cap' ), 10, 4 );
	}

	/**
	 * Intercepta a verificação de capacidade do WordPress.
	 *
	 * @param array  $caps    Capacidades requeridas.
	 * @param string $cap     Nome da capacidade (ex: edit_post).
	 * @param int    $user_id ID do usuário.
	 * @param array  $args    Argumentos extras (ID do post).
	 * @return array
	 */
	public function filter_map_meta_cap( array $caps, string $cap, int $user_id, array $args ): array {
		if ( ! in_array( $cap, array( 'edit_post', 'delete_post' ), true ) ) {
			return $caps;
		}

		$post_id = isset( $args[0] ) ? (int) $args[0] : 0;
		if ( ! $post_id ) {
			return $caps;
		}

		$current_status = $this->status_provider->get_status( $post_id );
		if ( ! $current_status ) {
			return $caps;
		}

		$statuses_to_block = apply_filters( 'sultswriten_blocked_statuses', $this->blocked_statuses );
		$roles_to_block    = apply_filters( 'sultswriten_blocked_roles', $this->blocked_roles );

		if ( ! in_array( $current_status, $statuses_to_block, true ) ) {
			return $caps;
		}

		$user_roles = $this->user_provider->get_current_user_roles();

		if ( array_intersect( $roles_to_block, $user_roles ) ) {
			return array( 'do_not_allow' );
		}

		return $caps;
	}
}
