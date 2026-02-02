<?php
/**
 * Desabilitador de funcionalidades nativas do WordPress.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */

namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\HookableInterface;

/**
 * Classe FeatureDisabler.
 *
 * Responsável por remover funcionalidades padrão do WordPress que não são utilizadas
 * no fluxo de trabalho editorial do plugin (como Tags, Comentários e Trackbacks).
 *
 * Isso mantém a interface do editor mais limpa e focada na produção de conteúdo.
 *
 * @see HookableInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @author     Sults
 * @since      0.1.0
 */
class FeatureDisabler implements HookableInterface {

	/**
	 * {@inheritDoc}
	 *
	 * Registra os ganchos para desabilitar taxonomias e suportes no 'init',
	 * e força o fechamento de comentários e pings via filtros.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'disable_features' ) );
		
		// Força o retorno 'false' para status de comentários e pings.
		add_filter( 'comments_open', '__return_false', 20 );
		add_filter( 'pings_open', '__return_false', 20 );
	}

	/**
	 * Executa a remoção das funcionalidades.
	 *
	 * Remove a taxonomia 'post_tag' (Tags) e os suportes a comentários/trackbacks
	 * do tipo de post padrão ('post').
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function disable_features(): void {
		unregister_taxonomy_for_object_type( 'post_tag', 'post' );

		remove_post_type_support( 'post', 'comments' );
		remove_post_type_support( 'post', 'trackbacks' );
	}
}