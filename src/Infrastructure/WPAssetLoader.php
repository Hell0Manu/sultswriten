<?php
/**
 * Implementação do carregador de assets usando funções do WordPress.
 *
 * Wrapper concreto para wp_enqueue_script, wp_enqueue_style e wp_localize_script.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */

namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\AssetLoaderInterface;

class WPAssetLoader implements AssetLoaderInterface {
	public function enqueue_script( string $handle, string $src, array $deps = array(), $ver = false, bool $in_footer = false ): void {
		wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
	}

	public function enqueue_style( string $handle, string $src, array $deps = array(), $ver = false, string $media = 'all' ): void {
		wp_enqueue_style( $handle, $src, $deps, $ver, $media );
	}

	public function localize_script( string $handle, string $object_name, array $l10n ): void {
		wp_localize_script( $handle, $object_name, $l10n );
	}
}
