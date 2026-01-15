<?php

namespace Sults\Writen\Utils;

class PathHelper {

    /**
     * Retorna o caminho relativo de um post (ex: /checklist/subcategoria/meu-post/).
     * Garante que funcione mesmo se o permalink ainda não foi salvo ou estiver cacheado errado.
     *
     * @param int $post_id ID do Post.
     * @return string O caminho relativo (sempre começando com /).
     */
    public static function get_relative_path( int $post_id ): string {
        $permalink = get_permalink( $post_id );
        $home_url  = home_url();

        $path = str_replace( $home_url, '', $permalink );

        if ( strpos( $path, '?p=' ) !== false ) {
            $sample = get_sample_permalink( $post_id );
            if ( ! empty( $sample[0] ) && ! empty( $sample[1] ) ) {
                $pretty_url = str_replace( '%postname%', $sample[1], $sample[0] );
                $path       = str_replace( $home_url, '', $pretty_url );
            }
        }

        $path = user_trailingslashit( $path );

        if ( substr( $path, 0, 1 ) !== '/' ) {
            $path = '/' . $path;
        }

        return $path;
    }
}