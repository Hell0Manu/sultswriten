<?php
/**
 * Helper para Manipulação de Caminhos e URLs.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Utils
 * @since      0.1.0
 */

namespace Sults\Writen\Utils;

/**
 * Classe PathHelper.
 *
 * Responsável por operações de formatação de URL e caminhos.
 *
 * Sua principal função é gerar caminhos relativos consistentes para exibição
 * no painel administrativo, garantindo que o usuário veja a estrutura hierárquica
 * da URL (slugs aninhados) mesmo em rascunhos ou se o permalink ainda não foi salvo.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Utils
 * @author     Sults
 * @since      0.1.0
 */
class PathHelper {

	/**
	 * Retorna o caminho relativo de um post (ex: /checklist/subcategoria/meu-post/).
	 *
	 * Remove o protocolo e o domínio da URL, retornando apenas o caminho (path).
	 * Inclui lógica de fallback para tentar prever a URL amigável mesmo se o WordPress
	 * retornar a URL padrão (ex: ?p=123) por questões de cache ou status de rascunho.
	 *
	 * @since 0.1.0
	 *
	 * @param int $sults_post_id ID do Post.
	 * @return string O caminho relativo (sempre começando com /).
	 */
	public static function get_relative_path( int $sults_post_id ): string {
		$sults_permalink = get_permalink( $sults_post_id );
		$home_url        = home_url();

		// Remove a base da URL para obter o relativo.
		$sults_path = str_replace( $home_url, '', $sults_permalink );

		// Se o WP retornar query string (ex: ?p=123), tentamos montar a URL bonita manualmente.
		if ( strpos( $sults_path, '?p=' ) !== false ) {
			$sample = get_sample_permalink( $sults_post_id );
			
			// Se o sample permalink estiver disponível, usamos ele.
			if ( ! empty( $sample[0] ) && ! empty( $sample[1] ) ) {
				$sults_pretty_url = str_replace( '%postname%', $sample[1], $sample[0] );
				$sults_path       = str_replace( $home_url, '', $sults_pretty_url );
			}
		}

		// Garante a barra no final (padrão SEO).
		$sults_path = user_trailingslashit( $sults_path );

		// Garante a barra no início.
		if ( substr( $sults_path, 0, 1 ) !== '/' ) {
			$sults_path = '/' . $sults_path;
		}

		return $sults_path;
	}
}