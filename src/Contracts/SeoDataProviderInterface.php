<?php
namespace Sults\Writen\Contracts;

interface SeoDataProviderInterface {
	/**
	 * Obtém os dados de SEO (Título e Descrição) para um determinado post.
	 *
	 * @param int $sults_post_id O ID do post.
	 * @return array Array associativo contendo ['title' => string, 'description' => string].
	 */
	public function get_seo_data( int $sults_post_id ): array;
}
