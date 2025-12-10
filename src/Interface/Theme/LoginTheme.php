<?php
/**
 * Gerencia a personalização da tela de login do WordPress.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface\Theme
 */

namespace Sults\Writen\Interface\Theme;

use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Contracts\HookableInterface;

class LoginTheme implements HookableInterface {

	private string $plugin_url;
	private string $plugin_version;
	private AssetLoaderInterface $asset_loader;

	public function __construct(
		string $plugin_url,
		string $plugin_version,
		AssetLoaderInterface $asset_loader
	) {
		$this->plugin_url     = $plugin_url;
		$this->plugin_version = $plugin_version;
		$this->asset_loader   = $asset_loader;
	}

	public function register(): void {
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_filter( 'login_headerurl', array( $this, 'change_logo_url' ) );
		add_filter( 'login_headertext', array( $this, 'change_logo_title' ) );
	}

	public function enqueue_styles(): void {
		$this->asset_loader->enqueue_style(
			'sultswriten-variables',
			$this->plugin_url . 'src/assets/css/variables.css',
			array(),
			$this->plugin_version
		);

		$this->asset_loader->enqueue_style(
			'sultswriten-login',
			$this->plugin_url . 'src/assets/css/login.css',
			array( 'sultswriten-variables', 'login' ),
			$this->plugin_version
		);

		$logo_url   = $this->plugin_url . 'src/assets/images/sults-logo.png';
		$custom_css = "
            #login h1 a, .login h1 a {
                background-image: url('{$logo_url}') !important;
                background-size: contain;
                width: 100%;
                max-width: 300px;
            }
        ";

		$this->asset_loader->add_inline_style( 'sultswriten-login', $custom_css );
	}

	public function change_logo_url(): string {
		return home_url();
	}

	public function change_logo_title(): string {
		return get_bloginfo( 'name' );
	}
}
