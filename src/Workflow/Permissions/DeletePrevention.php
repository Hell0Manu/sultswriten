<?php

namespace Sults\Writen\Workflow\Permissions;

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

        $post_id = isset( $args[0] ) ? $args[0] : 0;
        if ( ! $post_id ) {
            return $caps;
        }

        if ( get_post_status( $post_id ) === 'trash' ) {
            $user = get_userdata( $user_id );
            
            // Bloqueia a exclusão permanente para editores (Redator-Chefe) e abaixo.
            if ( $user && in_array( 'editor', (array) $user->roles, true ) ) {
                return array( 'do_not_allow' );
            }
        }

        return $caps;
    }
}
