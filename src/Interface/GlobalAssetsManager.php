<?php
namespace Sults\Writen\Interface;

use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Infrastructure\AssetPathResolver;

class GlobalAssetsManager implements HookableInterface {

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
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_assets' ), 1 );

		add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_styles' ) );
	}

	/**
	 * Centraliza o registro (wp_register_style/script) dos assets.
	 */
	public function register_admin_assets(): void {
		$version = $this->asset_resolver->get_version();

		// 1. Variáveis CSS (Base)
		wp_register_style(
			'sults-writen-variables',
			$this->asset_resolver->get_css_url( 'variables.css' ),
			array(),
			$version
		);

		// 2. Status Manager (CSS & JS)
		wp_register_style(
			'sults-writen-status-css',
			$this->asset_resolver->get_css_url( 'statusmanager.css' ),
			array( 'sults-writen-variables' ),
			$version
		);

		wp_register_script(
			'sults-writen-status-js',
			$this->asset_resolver->get_js_url( 'statusManager.js' ),
			array( 'jquery' ),
			$version,
			true
		);

		// 3. Structure (CSS & JS)
		wp_register_style(
			'sults-writen-structure-css',
			$this->asset_resolver->get_css_url( 'structure.css' ),
			array( 'sults-writen-variables', 'sults-writen-status-css' ),
			$version
		);

		wp_register_script(
			'sults-writen-structure-js',
			$this->asset_resolver->get_js_url( 'structure.js' ),
			array( 'jquery', 'jquery-ui-sortable' ),
			$version,
			true
		);
	}

	public function enqueue_block_styles(): void {
		$version = $this->asset_resolver->get_version();

		$this->asset_loader->enqueue_style(
			'sults-writen-variables',
			$this->asset_resolver->get_css_url( 'variables.css' ),
			array(),
			$version
		);

		$this->asset_loader->enqueue_style(
			'sults-writen-global',
			$this->asset_resolver->get_css_url( 'global.css' ),
			array( 'sults-writen-variables' ),
			$version
		);
	}
}
