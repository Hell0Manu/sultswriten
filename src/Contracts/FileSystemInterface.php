<?php
/**
 * Interface de Sistema de Arquivos.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

/**
 * Interface FileSystemInterface.
 *
 * Abstração sobre o sistema de arquivos (WP_Filesystem ou nativo PHP),
 * permitindo operações de leitura, escrita e exclusão de forma segura.
 */
interface FileSystemInterface {
	/**
	 * Verifica se um arquivo ou diretório existe.
	 *
	 * @since 0.1.0
	 * @param string $sults_path Caminho completo.
	 * @return bool
	 */
	public function exists( string $sults_path ): bool;

	/**
	 * Lê o conteúdo de um arquivo.
	 *
	 * @since 0.1.0
	 * @param string $sults_path Caminho do arquivo.
	 * @return string|false Conteúdo ou false se falhar.
	 */
	public function get_contents( string $sults_path );

	/**
	 * Escreve conteúdo em um arquivo.
	 *
	 * @since 0.1.0
	 * @param string $sults_path Caminho do arquivo.
	 * @param string $content    Conteúdo a escrever.
	 * @param int    $mode       Permissões (ex: 0644).
	 * @return bool Sucesso ou falha.
	 */
	public function put_contents( string $sults_path, string $content, int $mode = 0644 ): bool;

	/**
	 * Deleta um arquivo ou diretório.
	 *
	 * @since 0.1.0
	 * @param string $sults_path Caminho.
	 * @param bool   $recursive  Se deve deletar recursivamente (para diretórios).
	 * @return bool Sucesso ou falha.
	 */
	public function delete( string $sults_path, bool $recursive = false ): bool;

	/**
	 * Cria um diretório.
	 *
	 * @since 0.1.0
	 * @param string      $sults_path Caminho do diretório.
	 * @param int|bool    $chmod      Permissões.
	 * @param string|bool $chown      Proprietário.
	 * @param string|bool $chgrp      Grupo.
	 * @return bool
	 */
	public function mkdir( string $sults_path, $chmod = false, $chown = false, $chgrp = false ): bool;

	/**
	 * Retorna o caminho para o diretório temporário do sistema.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function get_temp_dir(): string;

	/**
	 * Inicializa as credenciais do sistema de arquivos WP se necessário.
	 *
	 * @since 0.1.0
	 * @return bool
	 */
	public function initialize(): bool;
}
