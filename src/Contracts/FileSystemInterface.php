<?php

namespace Sults\Writen\Contracts;

interface FileSystemInterface {
	/**
	 * Verifica se um arquivo ou diretório existe.
	 */
	public function exists( string $path ): bool;

	/**
	 * Lê o conteúdo de um arquivo.
	 */
	public function get_contents( string $path );

	/**
	 * Escreve conteúdo em um arquivo.
	 */
	public function put_contents( string $path, string $content, int $mode = 0644 ): bool;

	/**
	 * Deleta um arquivo ou diretório.
	 */
	public function delete( string $path, bool $recursive = false ): bool;

	/**
	 * Cria um diretório.
	 *
	 * @param string     $path  Caminho do diretório.
	 * @param int|bool   $chmod Permissões (ex: 0755) ou false para padrão.
	 * @param string|bool $chown Proprietário ou false.
	 * @param string|bool $chgrp Grupo ou false.
	 */
	public function mkdir( string $path, $chmod = false, $chown = false, $chgrp = false ): bool;

	/**
	 * Retorna o caminho para o diretório de uploads temporários do WP.
	 */
	public function get_temp_dir(): string;
	
	/**
	 * Inicializa o sistema de arquivos (necessário para WP_Filesystem).
	 */
	public function initialize(): bool;
}