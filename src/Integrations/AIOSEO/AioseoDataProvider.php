<?php
namespace Sults\Writen\Integrations\AIOSEO;

use Sults\Writen\Contracts\SeoDataProviderInterface;
use WP_Post;

class AioseoDataProvider implements SeoDataProviderInterface {

	/**
	 * Obtém os dados do AIOSEO. Se o plugin não estiver ativo, retorna valores padrão do WP.
	 */
	public function get_seo_data( int $post_id ): array {
		$default_data = array(
			'title'       => get_the_title( $post_id ),
			'description' => '',
		);

		if ( ! function_exists( 'aioseo' ) ) {
			return $default_data;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return $default_data;
		}

		try {
			$meta = aioseo()->meta->metaData->getMetaData( $post );

			if ( ! $meta ) {
				return $default_data;
			}

			$raw_title = ! empty( $meta->title ) ? $meta->title : aioseo()->meta->title->getTitle( $post_id );
			$seo_title = aioseo()->tags->replaceTags( $raw_title, $post_id );

			$raw_desc = ! empty( $meta->description ) ? $meta->description : aioseo()->meta->description->getDescription( $post_id );
			$seo_desc = aioseo()->tags->replaceTags( $raw_desc, $post_id );

			return array(
				'title'       => $seo_title ? $seo_title : $default_data['title'],
				'description' => $seo_desc,
			);

		} catch ( \Exception $e ) {
			return $default_data;
		}
	}
}
