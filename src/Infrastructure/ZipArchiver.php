<?php
namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\ArchiverInterface;
use ZipArchive;

class ZipArchiver implements ArchiverInterface {
	/**
	 * Cria um arquivo ZIP com os arquivos e strings fornecidos.
	 *
	 * @param string $output_path O caminho onde o arquivo ZIP será salvo.
	 * @param array $files_map Mapa de arquivos reais para caminhos dentro do ZIP.
	 * @param array $string_map Mapa de nomes de arquivos para conteúdos de strings.
	 * @return bool Retorna true se o arquivo ZIP foi criado com sucesso, false caso contrário.
	 */
	public function create( string $output_path, array $files_map, array $string_map ): bool {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return false;
		}

		$zip = new ZipArchive();

		if ( $zip->open( $output_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
			return false;
		}

		foreach ( $files_map as $real_path => $zip_path ) {
			if ( file_exists( $real_path ) ) {
				$zip->addFile( $real_path, $zip_path );
			}
		}

		foreach ( $string_map as $filename => $content ) {
			$zip->addFromString( $filename, $content );
		}

		return $zip->close();
	}
}
