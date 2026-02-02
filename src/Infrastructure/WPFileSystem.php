<?php
/**
 * Implementação do sistema de arquivos via WP_Filesystem.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */

namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\FileSystemInterface;

/**
 * Classe WPFileSystem.
 *
 * Wrapper para a API global `$wp_filesystem` do WordPress.
 *
 * O objetivo desta classe é abstrair as chamadas globais, permitindo que as operações
 * de disco sejam testáveis e garantindo que o sistema de arquivos esteja
 * inicializado antes de qualquer operação.
 *
 * @see FileSystemInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @author     Sults
 * @since      0.1.0
 */
class WPFileSystem implements FileSystemInterface {

	/**
	 * A instância global do filesystem.
	 *
	 * @var \WP_Filesystem_Base|null
	 */
	private $filesystem;

	/**
	 * Inicializa o sistema de arquivos.
	 *
	 * Carrega o arquivo `file.php` do admin se necessário e invoca `WP_Filesystem()`
	 * para popular a variável global `$wp_filesystem`.
	 *
	 * @since 0.1.0
	 * @return bool True se inicializado com sucesso, False caso contrário.
	 */
	public function initialize(): bool {
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$this->filesystem = $wp_filesystem;

		return ! empty( $this->filesystem );
	}

	/**
	 * Garante que o filesystem foi inicializado antes de uma operação.
	 *
	 * Se a propriedade `$filesystem` estiver nula, tenta inicializar.
	 *
	 * @return void
	 */
	private function ensure_initialized(): void {
		if ( ! $this->filesystem ) {
			$this->initialize();
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function exists( string $sults_path ): bool {
		$this->ensure_initialized();
		// Verifica se o objeto foi carregado corretamente antes de chamar o método.
		if ( ! $this->filesystem ) {
			return false;
		}
		return $this->filesystem->exists( $sults_path );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_contents( string $sults_path ) {
		$this->ensure_initialized();
		if ( ! $this->filesystem ) {
			return false;
		}
		return $this->filesystem->get_contents( $sults_path );
	}

	/**
	 * {@inheritDoc}
	 */
	public function put_contents( string $sults_path, string $content, int $mode = 0644 ): bool {
		$this->ensure_initialized();
		if ( ! $this->filesystem ) {
			return false;
		}
		return $this->filesystem->put_contents( $sults_path, $content, $mode );
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete( string $sults_path, bool $recursive = false ): bool {
		$this->ensure_initialized();
		if ( ! $this->filesystem ) {
			return false;
		}
		return $this->filesystem->delete( $sults_path, $recursive );
	}

	/**
	 * {@inheritDoc}
	 */
	public function mkdir( string $sults_path, $chmod = false, $chown = false, $chgrp = false ): bool {
		$this->ensure_initialized();
		if ( ! $this->filesystem ) {
			return false;
		}
		return $this->filesystem->mkdir( $sults_path, $chmod, $chown, $chgrp );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_temp_dir(): string {
		return get_temp_dir();
	}
}