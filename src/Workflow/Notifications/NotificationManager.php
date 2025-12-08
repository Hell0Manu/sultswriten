<?php
/**
 * Gerencia as notificações de mudança de status.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Notifications
 */

namespace Sults\Writen\Workflow\Notifications;

use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;

class NotificationManager {
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
		add_action( 'transition_post_status', array( $this, 'notify_author_on_status_change' ), 10, 3 );
	}

	public function notify_author_on_status_change( string $new_status, string $old_status, \WP_Post $post ): void {
		if ( $new_status === $old_status || $new_status === 'auto-draft' || $post->post_type !== 'post' ) {
			return;
		}

		$current_user_id = $this->user_provider->get_current_user_id();

		// Se quem está editando é o próprio dono do post, não notificamos.
		if ( $current_user_id === (int) $post->post_author ) {
			return;
		}

		$status_obj   = $this->status_provider->get_status_object( $new_status );
		$status_label = ( $status_obj && isset( $status_obj->label ) ) ? $status_obj->label : $new_status;

		$msg = sprintf(
			'O status do seu artigo <strong>"%s"</strong> mudou para <span class="sults-status-badge sults-status-%s">%s</span>.',
			$post->post_title,
			esc_attr( $new_status ),
			esc_html( $status_label )
		);

		$notification = array(
			'id'      => uniqid(),
			'time'    => time(),
			'msg'     => $msg,
			'post_id' => $post->ID,
			'read'    => false,
		);

		$user_notifs = $this->user_provider->get_user_meta( $post->post_author, '_sults_user_notifications', true );

		if ( ! is_array( $user_notifs ) ) {
			$user_notifs = array();
		}

		array_unshift( $user_notifs, $notification );
		$user_notifs = array_slice( $user_notifs, 0, 20 );

		$this->user_provider->update_user_meta( $post->post_author, '_sults_user_notifications', $user_notifs );
	}
}
