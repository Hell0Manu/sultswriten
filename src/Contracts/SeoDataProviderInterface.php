<?php
/**
 * Interface de Dados SEO.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

/**
 * Interface SeoDataProviderInterface.
 *
 * Abstrai a fonte dos dados de SEO (Título e Meta Description),
 * permitindo integração com plugins como AIOSEO, Yoast ou campos nativos.
 */
interface SeoDataProviderInterface {
	/**
	 * Obtém os dados de SEO para um post.
	 *
	 * @since 0.1.0
	 * @param int $sults_post_id O ID do post.
	 * @return array{title: string, description: string}
	 */
	public function get_seo_data( int $sults_post_id ): array;
}