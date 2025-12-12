<?php
namespace Sults\Writen\Interface\Theme;

use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Infrastructure\AssetPathResolver;

class LoginTheme implements HookableInterface {

	private AssetLoaderInterface $asset_loader;
	private AssetPathResolver $asset_resolver;

	public function __construct(
		AssetLoaderInterface $asset_loader,
		AssetPathResolver $asset_resolver
	) {
		$this->asset_loader   = $asset_loader;
		$this->asset_resolver = $asset_resolver;
	}

	public function register(): void {
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_filter( 'login_headerurl', array( $this, 'change_logo_url' ) );
		add_filter( 'login_headertext', array( $this, 'change_logo_title' ) );
	}

	public function enqueue_styles(): void {
		$version = $this->asset_resolver->get_version();

		$this->asset_loader->enqueue_style(
			'sultswriten-variables',
			$this->asset_resolver->get_css_url( 'variables.css' ),
			array(),
			$version
		);

		$this->asset_loader->enqueue_style(
			'sultswriten-login',
			$this->asset_resolver->get_css_url( 'login.css' ),
			array( 'sultswriten-variables', 'login' ),
			$version
		);

		$logo_url   = $this->asset_resolver->get_image_url( 'sults-logo.png' );
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
