<?php
/**
 * Implementação do repositório de posts via WordPress.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */

namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\PostRepositoryInterface;
use Sults\Writen\Workflow\PostStatus\PostStatusRegistrar;
use Sults\Writen\Workflow\Permissions\VisibilityPolicy;
use WP_Query;
use WP_Post;
use WP_Error;

/**
 * Classe WPPostRepository.
 *
 * Responsável por realizar consultas (WP_Query) e operações de escrita (wp_insert_post)
 * no banco de dados do WordPress.
 *
 * Esta classe implementa a interface `PostRepositoryInterface` e contém lógicas
 * específicas para filtrar quais posts aparecem no Workspace e quais são considerados finalizados.
 *
 * @see PostRepositoryInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @author     Sults
 * @since      0.1.0
 */
class WPPostRepository implements PostRepositoryInterface {

	/**
	 * Política de visibilidade.
	 *
	 * Mantemos a política injetada para compatibilidade e uso futuro,
	 * embora métodos específicos como o `get_posts_for_workspace` implementem
	 * suas próprias regras de filtragem de autor.
	 *
	 * @since 0.1.0
	 * @var VisibilityPolicy
	 */
	private VisibilityPolicy $visibility_policy;

	/**
	 * Construtor do repositório.
	 *
	 * @since 0.1.0
	 * @param VisibilityPolicy $visibility_policy Serviço de regras de visibilidade.
	 */
	public function __construct( VisibilityPolicy $visibility_policy ) {
		$this->visibility_policy = $visibility_policy;
	}

	/**
	 * Busca os posts para o Workspace (apenas pendentes do usuário).
	 *
	 * Esta query é otimizada para o fluxo de trabalho do usuário logado.
	 * Ela constrói uma lista de status permitidos combinando:
	 * 1. Status nativos do WP (exceto 'future' e 'private').
	 * 2. Status personalizados do plugin (via PostStatusRegistrar).
	 *
	 * Desta lista combinada, remove 'publish' e 'finished' para focar apenas
	 * no que está "Em Andamento".
	 *
	 * @since 0.1.0
	 * @see PostStatusRegistrar::get_custom_statuses()
	 *
	 * @param int $sults_author_id O ID do usuário logado.
	 * @return WP_Query Query contendo os posts pendentes do autor.
	 */
	public function get_posts_for_workspace( int $sults_author_id ): WP_Query {

		// Recupera status nativos visíveis no admin.
		$core_statuses = get_post_stati(
			array(
				'show_in_admin_all_list' => true,
				'_builtin'               => true,
			),
			'names'
		);
		// Remove status de agendamento e privados.
		$core_statuses = array_diff( $core_statuses, array( 'future', 'private' ) );

		// Recupera status personalizados registrados pelo plugin.
		$custom_statuses    = array_keys( PostStatusRegistrar::get_custom_statuses() );
		$sults_all_statuses = array_merge( $core_statuses, $custom_statuses );

		// Remove os status finais para exibir apenas tarefas ativas.
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
	 * Busca posts finalizados com filtros tipados.
	 *
	 * Utilizado na tela de exportação ou listagem de finalizados.
	 * Permite filtrar por busca textual, categoria e autor.
	 *
	 * @since 0.1.0
	 *
	 * @param int         $page        Página atual da paginação (Padrão: 1).
	 * @param string|null $search      Termo de busca textual (s).
	 * @param int|null    $category_id ID da categoria para filtro (cat).
	 * @param int|null    $author_id   ID do autor para filtro.
	 * @return WP_Query Query com os resultados paginados.
	 */
	public function get_finished_posts( 
		int $page = 1, 
		?string $search = null, 
		?int $category_id = null, 
		?int $author_id = null 
	): WP_Query {
		
		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'finished',
			'posts_per_page' => 20,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		if ( ! empty( $author_id ) ) {
			$args['author'] = $author_id;
		}

		if ( ! empty( $category_id ) ) {
			$args['cat'] = $category_id;
		}

		return new WP_Query( $args );
	}

	/**
	 * Busca um post pelo ID.
	 *
	 * Wrapper seguro para `get_post` que garante o retorno tipado.
	 *
	 * @since 0.1.0
	 * @param int $id ID do Post.
	 * @return \WP_Post|null Objeto do post ou null se não encontrado/inválido.
	 */
	public function find( int $id ): ?\WP_Post {
		$sults_post = get_post( $id );
		return $sults_post instanceof \WP_Post ? $sults_post : null;
	}

	/**
	 * Cria um novo post no banco de dados.
	 *
	 * @since 0.1.0
	 * @see wp_insert_post()
	 *
	 * @param array $data Dados compatíveis com wp_insert_post.
	 * @return int|\WP_Error ID do post criado ou objeto de erro.
	 */
	public function create( array $data ) {
		return wp_insert_post( $data, true );
	}

	/**
	 * Atualiza um post existente.
	 *
	 * @since 0.1.0
	 * @see wp_update_post()
	 *
	 * @param array $data Dados compatíveis com wp_update_post (deve conter 'ID').
	 * @return int|\WP_Error ID do post atualizado ou objeto de erro.
	 */
	public function update( array $data ) {
		return wp_update_post( $data, true );
	}

	/**
	 * Define termos para um post.
	 *
	 * Sanitiza os IDs dos termos usando `absint` antes de salvar.
	 *
	 * @since 0.1.0
	 * @see wp_set_post_terms()
	 *
	 * @param int    $sults_post_id  ID do Post.
	 * @param array  $sults_term_ids Array de IDs dos termos.
	 * @param string $taxonomy       Nome da taxonomia (padrão 'category').
	 * @return void
	 */
	public function set_terms( int $sults_post_id, array $sults_term_ids, string $taxonomy ): void {
		$sults_term_ids = array_map( 'absint', $sults_term_ids );
		wp_set_post_terms( $sults_post_id, $sults_term_ids, $taxonomy );
	}

	/**
	 * Busca posts filtrados por status para a estrutura visual.
	 *
	 * Retorna uma lista simples (array de objetos) ordenada por menu_order e título.
	 * Útil para montar árvores hierárquicas.
	 *
	 * @since 0.1.0
	 *
	 * @param array $statuses Lista de slugs de status a incluir.
	 * @return array Lista de objetos WP_Post.
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
	 *
	 * Recupera todos os posts independente do status (exceto lixo/auto-draft),
	 * para popular dropdowns de atributos de página (Parent Post).
	 *
	 * @since 0.1.0
	 * @return array Lista de todos os posts.
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
