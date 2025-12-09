<?php
namespace Sults\Writen\Interface\Dashboard;

use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\NotificationRepositoryInterface;
use Sults\Writen\Contracts\PostRepositoryInterface;
use Sults\Writen\Workflow\PostStatus\PostStatusRegistrar;

class WorkspaceController {

	private WPUserProviderInterface $user_provider;
	private NotificationRepositoryInterface $notification_repo;
	private PostRepositoryInterface $post_repo;

	public const PAGE_SLUG = 'sults-writen-workspace';

	public function __construct(
		WPUserProviderInterface $user_provider,
		NotificationRepositoryInterface $notification_repo,
		PostRepositoryInterface $post_repo
	) {
		$this->user_provider     = $user_provider;
		$this->notification_repo = $notification_repo;
		$this->post_repo         = $post_repo;
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_init', array( $this, 'redirect_default_dashboard' ) );
	}

	public function redirect_default_dashboard(): void {
		global $pagenow;

		if ( 'index.php' === $pagenow && ! $this->is_admin() ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
			exit;
		}
	}

	public function add_menu_page(): void {
		$user_id       = $this->user_provider->get_current_user_id();
		$notifications = $this->notification_repo->get_notifications( $user_id );
		$unread        = $this->count_unread_notifications( $notifications );

		$menu_title = 'Workspace';
		if ( $unread > 0 ) {
			$menu_title .= sprintf(
				' <span class="update-plugins count-%1$d"><span class="plugin-count">%1$d</span></span>',
				$unread
			);
		}

		add_menu_page(
			'Sults Workspace',
			$menu_title,
			'read',
			self::PAGE_SLUG,
			array( $this, 'render' ),
			'dashicons-dashboard',
			2
		);
	}

	public function handle_actions(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || 'sults-writen-workspace' !== $_GET['page'] ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['sults_action'] ) ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'sults_workspace_action' ) ) {
			return;
		}

		$user_id = $this->user_provider->get_current_user_id();

		$action = sanitize_text_field( wp_unslash( $_GET['sults_action'] ) );

		if ( 'clear_notifs' === $action ) {
			$this->notification_repo->clear_all_notifications( $user_id );
		} elseif ( 'dismiss_notif' === $action && isset( $_GET['notif_id'] ) ) {
			$notif_id = sanitize_text_field( wp_unslash( $_GET['notif_id'] ) );
			$this->notification_repo->dismiss_notification( $user_id, $notif_id );
		}

		wp_safe_redirect( remove_query_arg( array( 'sults_action', 'notif_id', '_wpnonce' ) ) );
		exit;
	}

	public function render(): void {
        $user_id = $this->user_provider->get_current_user_id();

        $my_posts = $this->post_repo->get_posts_for_workspace( $user_id );

        $notifications = $this->notification_repo->get_notifications( $user_id );
        $unread_count  = $this->count_unread_notifications( $notifications );

        $roles = $this->user_provider->get_current_user_roles();

        $sultswriten_restricted_statuses = apply_filters( 'sultswriten_blocked_statuses', PostStatusRegistrar::get_restricted_statuses() );
        $restricted_roles                = apply_filters( 'sultswriten_blocked_roles', PostStatusRegistrar::get_restricted_roles() );

        $sultswriten_is_restricted_user = ! empty( array_intersect( $restricted_roles, $roles ) );

       
        require __DIR__ . '/views/workspace-home.php';
    }

	private function count_unread_notifications( array $notifications ): int {
		return count( array_filter( $notifications, fn( $n ) => empty( $n['read'] ) ) );
	}

	private function is_admin(): bool {
		$roles = $this->user_provider->get_current_user_roles();
		return in_array( 'administrator', $roles, true );
	}
}
