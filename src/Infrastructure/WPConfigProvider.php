<?php
namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\ConfigProviderInterface;

class WPConfigProvider implements ConfigProviderInterface {

	public function get_home_url(): string {
		return home_url();
	}

	public function get_internal_domain(): string {
		return 'sults.com.br';
	}

	public function get_downloads_base_path(): string {
		return '/sults/downloads/artigos/checklist/';
	}

	public function get_tips_icon_path(): string {
		return '/sults/images/icones/produtos/modulo-checklist.webp';
	}
}
