<?php
/**
 * Interface para carregamento de assets (CSS/JS).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

/**
 * Interface AssetLoaderInterface.
 *
 * Padroniza a forma como scripts e estilos são enfileirados no WordPress.
 */
interface AssetLoaderInterface {
	/**
	 * Enfileira um arquivo JavaScript.
	 *
	 * @since 0.1.0
	 * @param string      $handle    Identificador único do script.
	 * @param string      $src       URL do arquivo.
	 * @param array       $deps      Dependências (ex: ['jquery']).
	 * @param string|bool $ver       Versão do script.
	 * @param bool        $in_footer Se deve carregar no rodapé.
	 */
	public function enqueue_script( string $handle, string $src, array $deps = array(), $ver = false, bool $in_footer = false ): void;

	/**
	 * Enfileira um arquivo CSS.
	 *
	 * @since 0.1.0
	 * @param string      $handle Identificador único do estilo.
	 * @param string      $src    URL do arquivo.
	 * @param array       $deps   Dependências.
	 * @param string|bool $ver    Versão.
	 * @param string      $media  Mídia (all, print, screen).
	 */
	public function enqueue_style( string $handle, string $src, array $deps = array(), $ver = false, string $media = 'all' ): void;

	/**
	 * Injeta dados PHP para uso no JavaScript (wp_localize_script).
	 *
	 * @since 0.1.0
	 * @param string $handle      Handle do script alvo.
	 * @param string $object_name Nome da variável global JS.
	 * @param array  $l10n        Dados a serem injetados.
	 */
	public function localize_script( string $handle, string $object_name, array $l10n ): void;

	/**
	 * Adiciona CSS inline a um estilo registrado.
	 *
	 * @since 0.1.0
	 * @param string $handle Handle do estilo alvo.
	 * @param string $css    Código CSS.
	 */
	public function add_inline_style( string $handle, string $css ): void;
}
