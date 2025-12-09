<?php
namespace Sults\Writen\Workflow\Permissions;

use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;
use Sults\Writen\Workflow\PostStatus\PostStatusRegistrar;

class PostRedirectionManager {

	private WPUserProviderInterface $user_provider;
	private WPPostStatusProviderInterface $status_provider;

	public function __construct(
		WPUserProviderInterface $user_provider,
		WPPostStatusProviderInterface $status_provider
	) {
		$this->user_provider   = $user_provider;
		$this->status_provider = $status_provider;
	}

	public function register(): void {
		add_action( 'load-post.php', array( $this, 'maybe_redirect_from_edit_screen' ) );
	}

	public function maybe_redirect_from_edit_screen(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['post'] ) || ! isset( $_GET['action'] ) || 'edit' !== $_GET['action'] ) {
			return;
		}

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_id = (int) $_GET['post'];
		if ( ! $post_id ) {
			return;
		}

		$current_status = $this->status_provider->get_status( $post_id );
		$user_roles     = $this->user_provider->get_current_user_roles();

		$statuses_to_block = apply_filters( 'sultswriten_blocked_statuses', PostStatusRegistrar::get_restricted_statuses() );
		$roles_to_block    = apply_filters( 'sultswriten_blocked_roles', PostStatusRegistrar::get_restricted_roles() );

		if ( array_intersect( $roles_to_block, $user_roles ) && in_array( $current_status, $statuses_to_block, true ) ) {
			wp_safe_redirect( get_permalink( $post_id ) );
			exit;
		}
	}
}
