<?php

namespace Sults\Writen\Utils;

class PathHelper {

	/**
	 * Retorna o caminho relativo de um post (ex: /checklist/subcategoria/meu-post/).
	 * Garante que funcione mesmo se o permalink ainda não foi salvo ou estiver cacheado errado.
	 *
	 * @param int $sults_post_id ID do Post.
	 * @return string O caminho relativo (sempre começando com /).
	 */
	public static function get_relative_path( int $sults_post_id ): string {
		$sults_permalink = get_permalink( $sults_post_id );
		$home_url        = home_url();

		$sults_path = str_replace( $home_url, '', $sults_permalink );

		if ( strpos( $sults_path, '?p=' ) !== false ) {
			$sample = get_sample_permalink( $sults_post_id );
			if ( ! empty( $sample[0] ) && ! empty( $sample[1] ) ) {
				$sults_pretty_url = str_replace( '%postname%', $sample[1], $sample[0] );
				$sults_path       = str_replace( $home_url, '', $sults_pretty_url );
			}
		}

		$sults_path = user_trailingslashit( $sults_path );

		if ( substr( $sults_path, 0, 1 ) !== '/' ) {
			$sults_path = '/' . $sults_path;
		}

		return $sults_path;
	}
}
