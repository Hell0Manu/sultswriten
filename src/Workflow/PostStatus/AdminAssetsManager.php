<?php
namespace Sults\Writen\Workflow\PostStatus;

use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Infrastructure\AssetPathResolver;

class AdminAssetsManager {

	private AssetLoaderInterface $asset_loader;
	private WPUserProviderInterface $user_provider;
	private AssetPathResolver $asset_resolver;
	private array $allowed_roles = array( 'administrator', 'editor' );

	public function __construct(
		AssetLoaderInterface $asset_loader,
		WPUserProviderInterface $user_provider,
		AssetPathResolver $asset_resolver
	) {
		$this->asset_loader   = $asset_loader;
		$this->user_provider  = $user_provider;
		$this->asset_resolver = $asset_resolver;
	}

	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'edit.php' ), true ) ) {
			return;
		}

		$user_roles = $this->user_provider->get_current_user_roles();
		$version    = $this->asset_resolver->get_version();

		$this->asset_loader->enqueue_style(
			'sultswriten-variables-css',
			$this->asset_resolver->get_css_url( 'variables.css' ),
			array(),
			$version
		);

		$this->asset_loader->enqueue_style(
			'sultswriten-status-css',
			$this->asset_resolver->get_css_url( 'statusManager.css' ),
			array( 'sultswriten-variables-css' ),
			$version
		);

		$this->asset_loader->enqueue_script(
			'sultswriten-statuses',
			$this->asset_resolver->get_js_url( 'statusManager.js' ),
			array( 'jquery' ),
			$version,
			true
		);

		$this->asset_loader->localize_script(
			'sultswriten-statuses',
			'SultsWritenStatuses',
			array(
				'statuses'      => PostStatusRegistrar::get_custom_statuses(),
				'current_roles' => $user_roles,
				'allowed_roles' => apply_filters( 'sultswriten_allowed_status_roles', $this->allowed_roles ),
			)
		);
	}
}
