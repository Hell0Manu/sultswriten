<?php
/**
 * Implementação do carregador de assets usando funções do WordPress.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */

namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\AssetLoaderInterface;

/**
 * Classe WPAssetLoader.
 *
 * Wrapper concreto para as funções de enfileiramento de scripts e estilos do WordPress.
 * Permite que a lógica de assets seja testada e desacoplada das funções globais.
 *
 * @see AssetLoaderInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @author     Sults
 * @since      0.1.0
 */
class WPAssetLoader implements AssetLoaderInterface {

	/**
	 * {@inheritDoc}
	 */
	public function enqueue_script( string $handle, string $src, array $deps = array(), $ver = false, bool $in_footer = false ): void {
		wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
	}

	/**
	 * {@inheritDoc}
	 */
	public function enqueue_style( string $handle, string $src, array $deps = array(), $ver = false, string $media = 'all' ): void {
		wp_enqueue_style( $handle, $src, $deps, $ver, $media );
	}

	/**
	 * {@inheritDoc}
	 */
	public function localize_script( string $handle, string $object_name, array $l10n ): void {
		wp_localize_script( $handle, $object_name, $l10n );
	}

	/**
	 * {@inheritDoc}
	 */
	public function add_inline_style( string $handle, string $css ): void {
		wp_add_inline_style( $handle, $css );
	}
}