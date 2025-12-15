<?php
namespace Sults\Writen\Infrastructure;

class AssetPathResolver {

	private string $base_url;
	private string $version;

	public function __construct( string $base_url, string $version ) {
		$this->base_url = $base_url;
		$this->version  = $version;
	}

	public function get_css_url( string $filename ): string {
		return $this->base_url . 'src/assets/css/' . $filename;
	}

	public function get_js_url( string $filename ): string {
		return $this->base_url . 'src/assets/js/' . $filename;
	}

	public function get_image_url( string $filename ): string {
		return $this->base_url . 'src/assets/images/' . $filename;
	}

	public function get_version(): string {
		return $this->version;
	}
}
