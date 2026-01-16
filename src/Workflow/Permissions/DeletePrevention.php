<?php

namespace Sults\Writen\Workflow\Permissions;

use Sults\Writen\Workflow\Permissions\RoleDefinitions;

/**
 * Responsável por impedir a exclusão permanente de posts por não-admins.
 */
class DeletePrevention {

	public function register(): void {
		add_filter( 'map_meta_cap', array( $this, 'prevent_permanent_delete' ), 10, 4 );
	}

	public function prevent_permanent_delete( array $caps, string $cap, int $user_id, array $args ): array {
		if ( 'delete_post' !== $cap ) {
			return $caps;
		}

		$sults_post_id = isset( $args[0] ) ? $args[0] : 0;
		if ( ! $sults_post_id ) {
			return $caps;
		}

		if ( get_post_status( $sults_post_id ) === 'trash' ) {
			$user = get_userdata( $user_id );

			if ( $user && in_array( RoleDefinitions::EDITOR_CHEFE, (array) $user->roles, true ) ) {
				return array( 'do_not_allow' );
			}
		}

		return $caps;
	}
}
