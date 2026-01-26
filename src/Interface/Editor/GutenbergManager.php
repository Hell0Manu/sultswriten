<?php
namespace Sults\Writen\Interface\Editor;

use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Infrastructure\AssetPathResolver;
use Sults\Writen\Workflow\PostStatus\StatusVisuals;

class GutenbergManager implements HookableInterface {

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
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_scripts' ) );

		add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_styles' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
	}

	public function enqueue_editor_scripts(): void {
		$version = $this->asset_resolver->get_version();

		$this->asset_loader->enqueue_script(
			'sults-writen-gutenberg-restrictions',
			$this->asset_resolver->get_js_url( 'gutenberg-restrictions.js' ),
			array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post', 'wp-hooks', 'lodash' ),
			$version,
			true
		);

		$this->asset_loader->enqueue_script(
			'sults-writen-gutenberg-workflow',
			$this->asset_resolver->get_js_url( 'gutenberg-workflow.js' ),
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'jquery' ),
			$version,
			true
		);

		$this->asset_loader->localize_script(
			'sults-writen-gutenberg-workflow',
			'sultsWorkflowParams',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'sults_structure_nonce' ),
			)
		);

		// $this->asset_loader->enqueue_script(
		// 'sults-writen-block-dica',
		// $this->asset_resolver->get_js_url( 'dica-block.js' ),
		// array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n' ),
		// $version,
		// true
		// );

		// $this->asset_loader->localize_script(
		// 'sults-writen-block-dica',
		// 'sultsWritenSettings',
		// array(
		// 'tipsIconUrl' => $this->asset_resolver->get_image_url( 'modulo-checklist.webp' ),
		// )
		// );
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
			'sults-writen-gutenberg-styles',
			$this->asset_resolver->get_css_url( 'gutenberg-styles.css' ),
			array( 'sults-writen-variables' ),
			$version
		);

		if ( class_exists( StatusVisuals::class ) ) {
			$status_css = StatusVisuals::get_css_rules();
			if ( ! empty( $status_css ) ) {
				wp_add_inline_style( 'sults-writen-gutenberg-styles', $status_css );
			}
		}
	}

	public function enqueue_frontend_scripts(): void {
		$version = $this->asset_resolver->get_version();

		$this->asset_loader->enqueue_script(
			'sults-writen-legacy-tips',
			$this->asset_resolver->get_js_url( 'legacy-tips-frontend.js' ),
			array( 'jquery' ),
			$version,
			true
		);

		$this->asset_loader->localize_script(
			'sults-writen-legacy-tips',
			'sultsWritenSettings',
			array(
				'tipsIconUrl' => $this->asset_resolver->get_image_url( 'modulo-checklist.webp' ),
			)
		);
	}
}
