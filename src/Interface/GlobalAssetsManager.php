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
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_styles' ) );
	}

	public function enqueue_styles(): void {
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
