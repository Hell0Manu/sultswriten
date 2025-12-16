<?php
namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\HookableInterface;

class NotFoundRedirector implements HookableInterface {

	public function register(): void {
		add_action( 'template_redirect', array( $this, 'handle_404_redirect' ) );
	}


	public function handle_404_redirect(): void {
		if ( ! is_404() ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=sults-writen-workspace' ) );
		exit;
	}
}