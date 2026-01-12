<?php
namespace Sults\Writen\Workflow\PostStatus;

use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Infrastructure\AssetPathResolver;
use Sults\Writen\Workflow\PostStatus\StatusVisuals;

class AdminAssetsManager {

	private AssetLoaderInterface $asset_loader;
	private WPUserProviderInterface $user_provider;
	private AssetPathResolver $asset_resolver;

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
			$this->asset_resolver->get_css_url( 'statusmanager.css' ),
			array( 'sultswriten-variables-css' ),
			$version
		);

		$custom_css = StatusVisuals::get_css_rules();
		$this->asset_loader->add_inline_style( 'sultswriten-status-css', $custom_css );

		$this->asset_loader->enqueue_script(
			'sultswriten-statuses',
			$this->asset_resolver->get_js_url( 'statusManager.js' ),
			array( 'jquery' ),
			$version,
			true
		);

		$all_configs = StatusConfig::get_all();
        $filtered_statuses = array();

        foreach ( $all_configs as $slug => $config ) {
            $allowed = isset( $config['flow_rules']['roles_allowed'] ) ? $config['flow_rules']['roles_allowed'] : array();

            $has_permission = false;

			foreach ( $user_roles as $role ) {
                if ( in_array( $role, $allowed, true ) ) {
                    $has_permission = true;
                    break;
                }
            }
            
            if ( $has_permission ) {
                $filtered_statuses[ $slug ] = $config['label'];
            }
        }

        $this->asset_loader->localize_script(
            'sultswriten-statuses',
            'SultsWritenStatuses',
            array(
                'statuses'      => $filtered_statuses, 
                'current_roles' => $user_roles,
                'allowed_roles' => $user_roles,
            )
        );
	}
}
