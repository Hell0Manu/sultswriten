<?php

    /**
     * Restringe a listagem de posts para que redatores (contributor) vejam:
     * 1. Apenas seus próprios posts (independentemente do status).
     * 2. Posts publicados de qualquer autor.
     *
     * @param string   $where Cláusula WHERE da consulta SQL.
     * @param \WP_Query $query Objeto WP_Query.
     * @return string
     */

namespace Sults\Writen\Workflow\Permissions;

use Sults\Writen\Contracts\WPUserProviderInterface;

class PostListVisibility {
    private WPUserProviderInterface $user_provider;

    public function __construct( WPUserProviderInterface $user_provider ) {
        $this->user_provider = $user_provider;
    }

    public function register(): void {
        add_filter( 'posts_where', array( $this, 'restrict_post_list_visibility' ), 99, 2 );
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

        $current_user_id = get_current_user_id();

        $sql_restriction = $wpdb->prepare(
            " AND ( 
                {$wpdb->posts}.post_status = 'publish' 
                OR {$wpdb->posts}.post_author = %d 
            )",
            $current_user_id
        );

        $where .= $sql_restriction;

        return $where;
    }
}
