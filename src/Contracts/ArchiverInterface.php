<?php
namespace Sults\Writen\Contracts;

interface ArchiverInterface {
	/**
	 * Cria um arquivo compactado.
	 *
	 * @param string $output_path Caminho completo onde o arquivo ZIP será salvo (ex: /tmp/arquivo.zip).
	 * @param array  $files_map   Lista de arquivos físicos. [ 'caminho/real/disco.jpg' => 'caminho/interno/zip/img.jpg' ]
	 * @param array  $string_map  Lista de arquivos gerados em memória. [ 'index.jsp' => 'conteúdo do jsp...' ]
	 * @return bool True em caso de sucesso, False em caso de falha.
	 */
	public function create( string $output_path, array $files_map, array $string_map ): bool;
}
