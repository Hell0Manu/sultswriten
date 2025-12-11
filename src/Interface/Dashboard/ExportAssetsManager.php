<?php
namespace Sults\Writen\Interface\Dashboard;

use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Contracts\HookableInterface;

class ExportAssetsManager implements HookableInterface {

	private string $plugin_url;
	private string $plugin_version;
	private AssetLoaderInterface $asset_loader;

	public function __construct( string $url, string $ver, AssetLoaderInterface $loader ) {
		$this->plugin_url     = $url;
		$this->plugin_version = $ver;
		$this->asset_loader   = $loader;
	}

	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets( string $hook ): void {

		if ( strpos( $hook, ExportController::PAGE_SLUG ) === false ) {
			return;
		}

		$this->asset_loader->enqueue_style(
			'sults-writen-variables-css',
			$this->plugin_url . 'src/assets/css/variables.css',
			array(),
			$this->plugin_version
		);

		$this->asset_loader->enqueue_style(
			'sults-modern-table-css',
			$this->plugin_url . 'src/assets/css/sults-table.css',
			array( 'sults-writen-variables-css' ),
			$this->plugin_version
		);

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['action'] ) && 'preview' === $_GET['action'] ) {

			wp_enqueue_code_editor( array( 'type' => 'text/html' ) );

			$this->asset_loader->enqueue_style(
				'sults-export-preview-css',
				$this->plugin_url . 'src/assets/css/export-preview.css',
				array( 'sults-writen-variables-css' ),
				$this->plugin_version
			);

			$this->asset_loader->enqueue_script(
				'sults-export-preview-js',
				$this->plugin_url . 'src/assets/js/export-preview.js',
				array( 'jquery', 'wp-theme-plugin-editor' ),
				$this->plugin_version,
				true
			);
		}
	}
}
