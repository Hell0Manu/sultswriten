<?php

namespace Sults\Writen\Contracts;

use WP_Query;
use WP_Post;

interface PostRepositoryInterface {
	public function get_posts_for_workspace( int $sults_author_id ): \WP_Query;

	/**
	 * Busca posts finalizados com filtros.
	 *
	 * @param array $filters Argumentos de filtro (s, author, cat, paged).
	 * @return \WP_Query
	 */
	public function get_finished_posts( int $page = 1, ?string $search = null, 
        ?int $category_id = null, 
        ?int $author_id = null 
    ): WP_Query;

	/**
	 * Busca um post pelo ID.
	 *
	 * @param int $id ID do Post.
	 * @return \WP_Post|null
	 */
	public function find( int $id ): ?\WP_Post;

	/**
	 * Cria um novo post.
	 *
	 * @param array $data Dados do post para wp_insert_post.
	 * @return int|\WP_Error ID do post ou erro.
	 */
	public function create( array $data );

	/**
	 * Atualiza um post existente.
	 *
	 * @param array $data Dados do post para wp_update_post (deve incluir ID).
	 * @return int|\WP_Error ID do post ou erro.
	 */
	public function update( array $data );

	/**
	 * Define termos para um post (ex: categorias).
	 *
	 * @param int    $sults_post_id  ID do Post.
	 * @param array  $sults_term_ids Array de IDs dos termos.
	 * @param string $taxonomy Taxonomia (category).
	 * @return void
	 */
	public function set_terms( int $sults_post_id, array $sults_term_ids, string $taxonomy ): void;

	/**
	 * Busca posts filtrados por uma lista de status (usado na árvore de estrutura).
	 *
	 * @param array $statuses Lista de slugs de status.
	 * @return array Lista de WP_Post.
	 */
	public function get_by_status( array $statuses ): array;

	/**
	 * Busca todos os posts que podem ser "Pais" (Parents) na estrutura.
	 *
	 * @return array Lista de WP_Post.
	 */
	public function get_all_for_parents(): array;
}
