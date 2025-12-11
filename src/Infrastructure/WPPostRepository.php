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
use Sults\Writen\Workflow\Permissions\VisibilityPolicy;
use WP_Query;

class WPPostRepository implements PostRepositoryInterface {

	/**
	 * Mantemos a política injetada para compatibilidade com o Plugin.php,
	 * mas para o Workspace vamos forçar a regra de "Meus Posts".
	 *
	 * @var VisibilityPolicy
	 */
	private VisibilityPolicy $visibility_policy;

	public function __construct( VisibilityPolicy $visibility_policy ) {
		$this->visibility_policy = $visibility_policy;
	}

	/**
	 * Busca os posts para o Workspace (apenas pendentes do usuário).
	 *
	 * @param int $author_id O ID do usuário logado.
	 * @return WP_Query
	 */
	public function get_posts_for_workspace( int $author_id ): WP_Query {

		// 1. Pega todos os status existentes
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

		$workspace_statuses = array_diff( $all_statuses, array( 'publish', 'finished' ) );

		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => 10,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'post_status'    => array_values( $workspace_statuses ),
			'author'         => $author_id, 
		);

		return new WP_Query( $args );
	}

	/**
	 * Busca posts finalizados com filtros (Exportação).
	 */
	public function get_finished_posts( array $filters ): WP_Query {
		$paged = ( isset( $filters['paged'] ) ) ? absint( $filters['paged'] ) : 1;
		
		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'finished', 
			'posts_per_page' => 20,
			'paged'          => $paged,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( ! empty( $filters['s'] ) ) {
			$args['s'] = sanitize_text_field( $filters['s'] );
		}

		if ( ! empty( $filters['author'] ) ) {
			$args['author'] = absint( $filters['author'] );
		}

		if ( ! empty( $filters['cat'] ) ) {
			$args['cat'] = absint( $filters['cat'] );
		}

		return new WP_Query( $args );
	}
}