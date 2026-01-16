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
use WP_Post;
use WP_Error;

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
	 * @param int $sults_author_id O ID do usuário logado.
	 * @return WP_Query
	 */
	public function get_posts_for_workspace( int $sults_author_id ): WP_Query {

		$core_statuses = get_post_stati(
			array(
				'show_in_admin_all_list' => true,
				'_builtin'               => true,
			),
			'names'
		);
		$core_statuses = array_diff( $core_statuses, array( 'future', 'private' ) );

		$custom_statuses    = array_keys( PostStatusRegistrar::get_custom_statuses() );
		$sults_all_statuses = array_merge( $core_statuses, $custom_statuses );

		$workspace_statuses = array_diff( $sults_all_statuses, array( 'publish', 'finished' ) );

		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => 10,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'post_status'    => array_values( $workspace_statuses ),
			'author'         => $sults_author_id,
		);

		return new WP_Query( $args );
	}

	/**
	 * Busca posts finalizados com filtros (Exportação).
	 */
	public function get_finished_posts( array $filters ): WP_Query {
		$sults_paged = ( isset( $filters['paged'] ) ) ? absint( $filters['paged'] ) : 1;

		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'finished',
			'posts_per_page' => 20,
			'paged'          => $sults_paged,
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

	/**
	 * Busca um post pelo ID.
	 */
	public function find( int $id ): ?\WP_Post {
		$sults_post = get_post( $id );
		return $sults_post instanceof \WP_Post ? $sults_post : null;
	}

	/**
	 * Cria um novo post.
	 */
	public function create( array $data ) {
		return wp_insert_post( $data, true );
	}

	/**
	 * Atualiza um post existente.
	 */
	public function update( array $data ) {
		return wp_update_post( $data, true );
	}

	/**
	 * Define termos para um post.
	 */
	public function set_terms( int $sults_post_id, array $sults_term_ids, string $taxonomy ): void {
		$sults_term_ids = array_map( 'absint', $sults_term_ids );
		wp_set_post_terms( $sults_post_id, $sults_term_ids, $taxonomy );
	}

	/**
	 * Busca posts filtrados por status para a estrutura.
	 */
	public function get_by_status( array $statuses ): array {
		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
			'post_status'    => $statuses,
		);
		return get_posts( $args );
	}

	/**
	 * Busca potenciais pais (todos os posts).
	 */
	public function get_all_for_parents(): array {
		return get_posts(
			array(
				'post_type'      => 'post',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => 'any',
			)
		);
	}
}
