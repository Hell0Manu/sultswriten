<?php

/**
 * Gera um nome de arquivo seguro e formatado para o ZIP de exportação.
 *
 * @param string $raw_title O título original do post.
 * @return string O nome do arquivo formatado (sem extensão).
 */

namespace Sults\Writen\Workflow\Export;

use Sults\Writen\Contracts\ExportNamingServiceInterface;

class ExportNamingService implements ExportNamingServiceInterface {

	public function generate_zip_filename( string $raw_title ): string {
		$base_name = sanitize_title( $raw_title );

		$char_limit = 50;
		if ( strlen( $base_name ) > $char_limit ) {
			$base_name = substr( $base_name, 0, $char_limit );
			$base_name = rtrim( $base_name, '-' );
		}

		if ( empty( $base_name ) ) {
			$base_name = 'exportacao-sults';
		}

		return $base_name;
	}
}
