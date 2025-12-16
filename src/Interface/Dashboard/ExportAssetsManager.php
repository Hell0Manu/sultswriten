<?php
namespace Sults\Writen\Interface\Dashboard;

use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Infrastructure\AssetPathResolver;
use Sults\Writen\Workflow\PostStatus\StatusVisuals;

class ExportAssetsManager implements HookableInterface {

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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets( string $hook ): void {

		if ( strpos( $hook, ExportController::PAGE_SLUG ) === false ) {
			return;
		}

		$version = $this->asset_resolver->get_version();

		$this->asset_loader->enqueue_style(
			'sults-writen-variables-css',
			$this->asset_resolver->get_css_url( 'variables.css' ),
			array(),
			$version
		);

		$this->asset_loader->enqueue_style(
			'sults-modern-table-css',
			$this->asset_resolver->get_css_url( 'sults-table.css' ),
			array( 'sults-writen-variables-css' ),
			$version
		);

		$this->asset_loader->enqueue_style(
			'sults-writen-status-css',
			$this->asset_resolver->get_css_url( 'statusManager.css' ),
			array( 'sults-writen-variables-css' ),
			$version
		);

		$this->asset_loader->add_inline_style( 'sults-writen-status-css', StatusVisuals::get_css_rules() );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['action'] ) && 'preview' === $_GET['action'] ) {

			wp_enqueue_code_editor( array( 'type' => 'text/html' ) );

			$this->asset_loader->enqueue_style(
				'sults-export-preview-css',
				$this->asset_resolver->get_css_url( 'export-preview.css' ),
				array( 'sults-writen-variables-css' ),
				$version
			);

			$this->asset_loader->enqueue_script(
				'sults-export-preview-js',
				$this->asset_resolver->get_js_url( 'export-preview.js' ),
				array( 'jquery', 'wp-theme-plugin-editor' ),
				$version,
				true
			);
		}
	}
}
