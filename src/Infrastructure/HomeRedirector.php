<?php
namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\HookableInterface;

class HomeRedirector implements HookableInterface {

	public function register(): void {
		add_action( 'template_redirect', array( $this, 'handle_home_redirect' ) );
	}

	public function handle_home_redirect(): void {
		if ( ! is_front_page() ) {
			return;
		}

		$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
		if ( strpos( $ua, 'WordPress' ) !== false ) {
			return;
		}

		if ( is_user_logged_in() ) {
			wp_safe_redirect( admin_url( 'admin.php?page=sults-writen-workspace' ) );
			exit;
		}

		wp_safe_redirect( wp_login_url() );
		exit;
	}
}
