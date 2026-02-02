<?php
/**
 * Interface para repositório de Posts.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

use WP_Query;
use WP_Post;

/**
 * Interface PostRepositoryInterface.
 *
 * Define os métodos para criar, ler e atualizar posts dentro do contexto do plugin.
 * Centraliza as queries de `WP_Query` para evitar espalhar lógica de busca pelo código.
 */
interface PostRepositoryInterface {
	/**
	 * Busca posts relevantes para o workspace de um autor.
	 *
	 * @since 0.1.0
	 * @param int $sults_author_id ID do autor para filtrar (opcional).
	 * @return WP_Query
	 */
	public function get_posts_for_workspace( int $sults_author_id ): \WP_Query;

	/**
	 * Busca posts finalizados aplicando filtros de pesquisa e categoria.
	 *
	 * @since 0.1.0
	 * @param int         $page        Número da página para paginação.
	 * @param string|null $search      Termo de busca.
	 * @param int|null    $category_id ID da categoria.
	 * @param int|null    $author_id   ID do autor.
	 * @return WP_Query
	 */
	public function get_finished_posts( int $page = 1, ?string $search = null, 
        ?int $category_id = null, 
        ?int $author_id = null 
    ): WP_Query;

	/**
	 * Encontra um post pelo ID.
	 *
	 * @since 0.1.0
	 * @param int $id ID do Post.
	 * @return WP_Post|null Objeto do post ou null se não encontrado.
	 */
	public function find( int $id ): ?\WP_Post;

	/**
	 * Cria um novo post no banco de dados.
	 *
	 * @since 0.1.0
	 * @param array $data Dados compatíveis com `wp_insert_post`.
	 * @return int|WP_Error ID do post criado ou erro.
	 */
	public function create( array $data );

	/**
	 * Atualiza um post existente.
	 *
	 * @since 0.1.0
	 * @param array $data Dados compatíveis com `wp_update_post` (deve conter 'ID').
	 * @return int|WP_Error ID do post atualizado ou erro.
	 */
	public function update( array $data );

	/**
	 * Define os termos (categorias/tags) de um post.
	 *
	 * @since 0.1.0
	 * @param int    $sults_post_id  ID do Post.
	 * @param array  $sults_term_ids Lista de IDs dos termos.
	 * @param string $taxonomy       Nome da taxonomia (padrão: 'category').
	 * @return void
	 */
	public function set_terms( int $sults_post_id, array $sults_term_ids, string $taxonomy ): void;

	/**
	 * Busca posts que estejam em determinados status.
	 * Utilizado principalmente para montar a árvore de estrutura visual.
	 *
	 * @since 0.1.0
	 * @param array<string> $statuses Lista de slugs de status (ex: ['published', 'draft']).
	 * @return array<WP_Post> Lista de objetos de post.
	 */
	public function get_by_status( array $statuses ): array;

	/**
	 * Busca todos os posts que são elegíveis para serem "Pais" na hierarquia.
	 *
	 * @since 0.1.0
	 * @return array<WP_Post>
	 */
	public function get_all_for_parents(): array;
}
