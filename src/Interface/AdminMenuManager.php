<?php
namespace Sults\Writen\Interface;

use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\HookableInterface;

class AdminMenuManager implements HookableInterface {

	private WPUserProviderInterface $user_provider;

	private const ALLOWED_FOR_CONTRIBUTOR = array(
		'sults-writen-workspace', // Workspace.
		'edit.php',               // Posts (Artigos).
		'upload.php',             // Mídia.
		'profile.php',            // Perfil.
	);

	public function __construct( WPUserProviderInterface $user_provider ) {
		$this->user_provider = $user_provider;
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'cleanup_menus' ), 999 );
	}

	public function cleanup_menus(): void {
		$roles = $this->user_provider->get_current_user_roles();

		if ( in_array( 'administrator', $roles, true ) ) {
			return;
		}

		if ( in_array( 'contributor', $roles, true ) ) {
			$this->apply_allowlist( self::ALLOWED_FOR_CONTRIBUTOR );
		}
	}

	/**
	 * Remove tudo do menu que NÃO estiver na lista de permitidos.
	 * * @param array $allowed_slugs Lista de slugs (ex: index.php, edit.php) que devem permanecer.
	 */
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
