<?php
/**
 * Interface para o carregador de assets.
 *
 * Define o contrato para classes que enfileiram scripts e estilos,
 * permitindo abstração e testes mais fáceis.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

interface AssetLoaderInterface {
	public function enqueue_script( string $handle, string $src, array $deps = array(), $ver = false, bool $in_footer = false ): void;
	public function enqueue_style( string $handle, string $src, array $deps = array(), $ver = false, string $media = 'all' ): void;
	public function localize_script( string $handle, string $object_name, array $l10n ): void;
	public function add_inline_style( string $handle, string $css ): void;
}
