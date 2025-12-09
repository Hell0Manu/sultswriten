<?php
/**
 * Implementação concreta do repositório de posts usando WP_Query.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 */

namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\PostRepositoryInterface;
use Sults\Writen\Workflow\PostStatus\PostStatusRegistrar;
use WP_Query;

class WPPostRepository implements PostRepositoryInterface {

	/**
	 * Busca os posts para o Workspace de um autor específico.
	 *
	 * @param int $author_id O ID do usuário autor.
	 * @return WP_Query
	 */
	public function get_posts_for_workspace( int $author_id ): WP_Query {

		$core_statuses = get_post_stati(
			array(
				'show_in_admin_all_list' => true,
				'_builtin'               => true,
			),
			'names'
		);
		$core_statuses = array_diff( $core_statuses, array( 'future', 'private' ) );

		$custom_statuses = array_keys( PostStatusRegistrar::get_custom_statuses() );
		$all_statuses    = array_merge( $core_statuses, $custom_statuses );

		$args = array(
			'author'         => $author_id,
			'post_type'      => 'post',
			'posts_per_page' => 10,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'post_status'    => array_values( $all_statuses ),
		);

		return new WP_Query( $args );
	}
}
