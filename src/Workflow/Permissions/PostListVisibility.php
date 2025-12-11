<?php
/**
 * Restringe a listagem de posts no Admin (edit.php).
 *
 * Usa a VisibilityPolicy para determinar se o usuário atual deve ter
 * sua visão restrita apenas aos seus posts ou a status específicos.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 */

namespace Sults\Writen\Workflow\Permissions;

class PostListVisibility {
	/**
	 * Política de visibilidade.
	 *
	 * @var VisibilityPolicy
	 */
	private VisibilityPolicy $visibility_policy;

	public function __construct( VisibilityPolicy $visibility_policy ) {
		$this->visibility_policy = $visibility_policy;
	}

	public function register(): void {
		add_filter( 'posts_where', array( $this, 'restrict_post_list_visibility' ), 99, 2 );
	}

	/**
	 * Aplica filtro SQL WHERE se o usuário for restrito.
	 *
	 * @param string   $where Cláusula WHERE atual.
	 * @param WP_Query $query Objeto da query.
	 * @return string Cláusula WHERE modificada.
	 */
	public function restrict_post_list_visibility( string $where, \WP_Query $query ): string {
		global $wpdb;

		if ( ! is_admin() || ! $query->is_main_query() ) {
			return $where;
		}

		if ( 'post' !== $query->get( 'post_type' ) ) {
			return $where;
		}

		if ( $this->visibility_policy->can_see_others_posts() ) {
			return $where;
		}

		$current_user_id  = get_current_user_id();
		$allowed_statuses = $this->visibility_policy->get_allowed_statuses_for_restricted_user();
		$placeholders     = implode( ', ', array_fill( 0, count( $allowed_statuses ), '%s' ) );

		$query_template = " AND ( 
            {$wpdb->posts}.post_author = %d 
            OR {$wpdb->posts}.post_status IN ( $placeholders )
        )";

		$args = array_merge( array( $current_user_id ), $allowed_statuses );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query dinâmica construída com segurança acima.
		$sql_restriction = $wpdb->prepare( $query_template, ...$args );

		$where .= $sql_restriction;

		return $where;
	}
}
