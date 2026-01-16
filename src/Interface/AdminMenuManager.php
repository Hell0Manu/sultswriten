<?php
namespace Sults\Writen\Interface;

use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Workflow\Permissions\RoleDefinitions;

class AdminMenuManager implements HookableInterface {

	private WPUserProviderInterface $user_provider;

	private const ALLOWED_PAGES = array(
		'sults-writen-workspace',
		'sults-writen-structure',
		'edit.php',
		'post-new.php',
		'post.php',
		'upload.php',
		'profile.php',
	);

	public function __construct( WPUserProviderInterface $user_provider ) {
		$this->user_provider = $user_provider;
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'cleanup_menus' ), 999 );
	}

	public function cleanup_menus(): void {
		remove_menu_page( 'edit-comments.php' );

		$roles = $this->user_provider->get_current_user_roles();

		if ( in_array( RoleDefinitions::ADMIN, $roles, true ) ) {
			return;
		}

		$this->apply_allowlist( self::ALLOWED_PAGES );
	}

	private function apply_allowlist( array $allowed_slugs ): void {
		global $menu;

		if ( empty( $menu ) ) {
			return;
		}

		foreach ( $menu as $index => $item ) {
			$slug = $item[2];

			if ( false !== strpos( $slug, 'separator' ) ) {
				continue;
			}

			if ( ! in_array( $slug, $allowed_slugs, true ) ) {
				remove_menu_page( $slug );
			}
		}

		if ( ! in_array( 'index.php', $allowed_slugs, true ) ) {
			remove_menu_page( 'index.php' );
		}
	}
}
