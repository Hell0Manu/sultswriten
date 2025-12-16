<?php
namespace Sults\Writen\Contracts;

interface ExportNamingServiceInterface {
	public function generate_zip_filename( string $raw_title ): string;
}
