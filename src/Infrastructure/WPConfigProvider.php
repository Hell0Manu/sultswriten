<?php
namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\ConfigProviderInterface;

class WPConfigProvider implements ConfigProviderInterface {

	public function get_home_url(): string {
		return home_url();
	}

	public function get_internal_domain(): string {
		return defined( 'SULTSWRITEN_INTERNAL_DOMAIN' ) ? SULTSWRITEN_INTERNAL_DOMAIN : 'sults.com.br';
	}

	public function get_downloads_base_path(): string {
		return defined( 'SULTSWRITEN_DOWNLOADS_PATH' ) ? SULTSWRITEN_DOWNLOADS_PATH : '/downloads/';
	}

	public function get_tips_icon_path(): string {
		return defined( 'SULTSWRITEN_TIPS_ICON' ) ? SULTSWRITEN_TIPS_ICON : '';
	}
}
