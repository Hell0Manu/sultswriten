<?php
/**
 * Provedor de Dados SEO via AIOSEO.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Integrations\AIOSEO
 * @since      0.1.0
 */

namespace Sults\Writen\Integrations\AIOSEO;

use Sults\Writen\Contracts\SeoDataProviderInterface;
use WP_Post;

/**
 * Classe AioseoDataProvider.
 *
 * Responsável por extrair metadados de SEO (Título e Descrição) utilizando a API
 * pública do plugin All in One SEO Pack (AIOSEO).
 *
 * Esta classe isola a dependência externa. Se mudarmos de plugin de SEO no futuro
 * (ex: para Yoast ou RankMath), basta criar um novo DataProvider sem alterar
 * o restante do sistema de exportação.
 *
 * @see SeoDataProviderInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Integrations\AIOSEO
 * @author     Sults
 * @since      0.1.0
 */
class AioseoDataProvider implements SeoDataProviderInterface {

	/**
	 * Obtém os dados de SEO processados para um post específico.
	 *
	 * Tenta recuperar o Título SEO e a Meta Descrição definidos no AIOSEO.
	 * Realiza o processamento de "Smart Tags" (ex: converter %current_year% para 2024).
	 *
	 * @since 0.1.0
	 *
	 * @param int $sults_post_id O ID do post.
	 * @return array{title: string, description: string} Array com 'title' e 'description'.
	 */
	public function get_seo_data( int $sults_post_id ): array {
		// Dados padrão (Fallback) caso o AIOSEO não esteja ativo ou configurado.
		$default_data = array(
			'title'       => get_the_title( $sults_post_id ),
			'description' => '',
		);

		// Verifica se o AIOSEO está ativo para evitar erros fatais.
		if ( ! function_exists( 'aioseo' ) ) {
			return $default_data;
		}

		$sults_post = get_post( $sults_post_id );
		if ( ! $sults_post instanceof WP_Post ) {
			return $default_data;
		}

		try {
			// Obtém o objeto de metadados do AIOSEO.
			$meta = \aioseo()->meta->metaData->getMetaData( $sults_post );

			if ( ! $meta ) {
				return $default_data;
			}

			// Título: Tenta pegar do objeto meta, senão usa o helper do AIOSEO.
			$raw_title = ! empty( $meta->title ) ? $meta->title : \aioseo()->meta->title->getTitle( $sults_post_id );
			
			// Processa as tags dinâmicas (ex: %post_title% -> "Meu Artigo").
			$seo_title = \aioseo()->tags->replaceTags( $raw_title, $sults_post_id );

			// Descrição: Mesma lógica do título.
			$raw_desc = ! empty( $meta->description ) ? $meta->description : \aioseo()->meta->description->getDescription( $sults_post_id );
			$seo_desc = \aioseo()->tags->replaceTags( $raw_desc, $sults_post_id );

			return array(
				'title'       => $seo_title ? $seo_title : $default_data['title'],
				'description' => $seo_desc,
			);

		} catch ( \Exception $e ) {
			// Em caso de qualquer erro interno do AIOSEO, retorna o padrão seguro.
			return $default_data;
		}
	}
}