<?php
namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\ArchiverInterface;
use ZipArchive;

class ZipArchiver implements ArchiverInterface {

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
