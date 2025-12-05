<?php
/**
 * Gerencia as permissões, capacidades e visibilidade dos usuários no Workflow.
 *
 * Responsável por:
 * 1. Renomear os papéis (labels) na interface (Ex: Contributor -> Redator).
 * 2. Restringir a biblioteca de mídia para que redatores vejam apenas seus uploads.
 * 3. Restringir a listagem de posts para que redatores não vejam rascunhos de outros.
 * 4. Impedir a exclusão permanente de posts por não-admins.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Permissions;

use Sults\Writen\Contracts\WPUserProviderInterface;

class RoleManager {
	private WPUserProviderInterface $user_provider;

	public function __construct( WPUserProviderInterface $user_provider ) {
		$this->user_provider = $user_provider;
	}

	public function register(): void {
		add_filter( 'editable_roles', array( $this, 'rename_roles' ) );
		add_filter( 'ajax_query_attachments_args', array( $this, 'limit_media_library' ) );
		add_filter( 'posts_where', array( $this, 'restrict_post_list_visibility' ), 10, 2 );
		add_filter( 'map_meta_cap', array( $this, 'prevent_permanent_delete' ), 10, 4 );
	}

	public function rename_roles( array $roles ): array {
		if ( isset( $roles['editor'] ) ) {
			$roles['editor']['name'] = 'Redator-Chefe';
		}

		if ( isset( $roles['contributor'] ) ) {
			$roles['contributor']['name'] = 'Redator';
		}

		if ( isset( $roles['author'] ) ) {
			$roles['author']['name'] = 'Corretor';
		}

		if ( isset( $roles['subscriber'] ) ) {
			$roles['subscriber']['name'] = 'Visitante';
		}

		return $roles;
	}

	public function limit_media_library( array $query ): array {
		$user_roles = $this->user_provider->get_current_user_roles();
		$user       = wp_get_current_user();

		if ( in_array( 'contributor', $user_roles, true ) ) {
			$query['author'] = $user->ID;
		}

		return $query;
	}

	public function restrict_post_list_visibility( string $where, \WP_Query $query ): string {
		global $wpdb;

		if ( ! is_admin() || ! $query->is_main_query() ) {
			return $where;
		}

		if ( 'post' !== $query->get( 'post_type' ) ) {
			return $where;
		}

		$user_roles = $this->user_provider->get_current_user_roles();

		if ( ! in_array( 'contributor', $user_roles, true ) ) {
			return $where;
		}

		$user            = wp_get_current_user();
		$current_user_id = absint( $user->ID );

		$where .= " AND ( 
            {$wpdb->posts}.post_status = 'publish' 
            OR {$wpdb->posts}.post_author = {$current_user_id} 
        )";

		return $where;
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

			if ( $user && in_array( 'editor', (array) $user->roles, true ) ) {
				return array( 'do_not_allow' );
			}
		}

		return $caps;
	}
}
