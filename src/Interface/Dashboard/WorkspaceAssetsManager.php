<?php
namespace Sults\Writen\Interface\Dashboard;

use Sults\Writen\Contracts\AssetLoaderInterface;

class WorkspaceAssetsManager {

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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets( string $hook ): void {
		if ( strpos( $hook, 'sults-writen-workspace' ) === false ) {
			return;
		}

		$this->asset_loader->enqueue_style(
			'sults-writen-variables-css',
			$this->plugin_url . 'src/assets/css/variables.css',
			array(),
			$this->plugin_version
		);

		$this->asset_loader->enqueue_style(
			'sults-writen-workspace-css',
			$this->plugin_url . 'src/assets/css/workspace.css',
			array( 'sults-writen-variables-css' ),
			$this->plugin_version
		);

		$this->asset_loader->enqueue_style(
			'sults-writen-status-css',
			$this->plugin_url . 'src/assets/css/statusManager.css',
			array( 'sults-writen-workspace-css' ),
			$this->plugin_version
		);
	}
}
