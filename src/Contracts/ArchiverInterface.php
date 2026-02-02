<?php
/**
 * Interface de Arquivamento (Zip).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

/**
 * Interface ArchiverInterface.
 *
 * Responsável por empacotar os arquivos gerados e assets estáticos em um arquivo ZIP final.
 */
interface ArchiverInterface {
	/**
	 * Cria um arquivo compactado ZIP.
	 *
	 * @since 0.1.0
	 * @param string $output_path Caminho completo de destino do ZIP.
	 * @param array  $files_map   Mapeamento de arquivos físicos [origem => destino_no_zip].
	 * @param array  $string_map  Mapeamento de arquivos em memória [nome_arquivo => conteudo_string].
	 * @return bool True em caso de sucesso.
	 */
	public function create( string $output_path, array $files_map, array $string_map ): bool;
}