<?php
namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\ConfigProviderInterface;

class WPConfigProvider implements ConfigProviderInterface {

	public function get_home_url(): string {
		return home_url();
	}

	public function get_internal_domains(): array {
		$domains = [
			'sults.com.br',
			'artigo.sults.com.br'
		];

		if ( defined( 'SULTSWRITEN_INTERNAL_DOMAIN' ) ) {
			$domains[] = SULTSWRITEN_INTERNAL_DOMAIN;
		}

		return array_unique( array_filter( array_map( 'trim', $domains ) ) );
	}

	public function get_downloads_base_path(): string {
		return defined( 'SULTSWRITEN_DOWNLOADS_PATH' ) ? SULTSWRITEN_DOWNLOADS_PATH : '/downloads/';
	}

	public function get_tips_icon_path(): string {
		return defined( 'SULTSWRITEN_TIPS_ICON' ) ? SULTSWRITEN_TIPS_ICON : '';
	}
}
