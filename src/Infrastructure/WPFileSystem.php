<?php

namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\FileSystemInterface;

class WPFileSystem implements FileSystemInterface {

	private $filesystem;

	public function initialize(): bool {
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$this->filesystem = $wp_filesystem;

		return ! empty( $this->filesystem );
	}

	private function ensure_initialized(): void {
		if ( ! $this->filesystem ) {
			$this->initialize();
		}
	}

	public function exists( string $path ): bool {
		$this->ensure_initialized();
		return $this->filesystem->exists( $path );
	}

	public function get_contents( string $path ) {
		$this->ensure_initialized();
		return $this->filesystem->get_contents( $path );
	}

	public function put_contents( string $path, string $content, int $mode = 0644 ): bool {
		$this->ensure_initialized();
		return $this->filesystem->put_contents( $path, $content, $mode );
	}

	public function delete( string $path, bool $recursive = false ): bool {
		$this->ensure_initialized();
		return $this->filesystem->delete( $path, $recursive );
	}

	public function mkdir( string $path, $chmod = false, $chown = false, $chgrp = false ): bool {
		$this->ensure_initialized();
		return $this->filesystem->mkdir( $path, $chmod, $chown, $chgrp );
	}

	public function get_temp_dir(): string {
		return get_temp_dir();
	}
}