<?php

namespace Sults\Writen\Contracts;

interface PostRepositoryInterface {
	public function get_posts_for_workspace( int $author_id ): \WP_Query;
	
	/**
     * Busca posts finalizados com filtros.
     *
     * @param array $filters Argumentos de filtro (s, author, cat, paged).
     * @return \WP_Query
     */
    public function get_finished_posts( array $filters ): \WP_Query;
}
