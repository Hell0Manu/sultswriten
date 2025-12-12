<?php
/**
 * Bloqueia a edição de posts delegando as regras para a WorkflowPolicy.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Permissions;

use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;
use Sults\Writen\Infrastructure\RequestBlocker;
use Sults\Writen\Workflow\WorkflowPolicy;

class PostEditingBlocker {
	private WPUserProviderInterface $user_provider;
	private WPPostStatusProviderInterface $status_provider;
	private RequestBlocker $request_blocker;
	private WorkflowPolicy $policy;

	public function __construct(
		WPUserProviderInterface $user_provider,
		WPPostStatusProviderInterface $status_provider,
		RequestBlocker $request_blocker,
		WorkflowPolicy $policy
	) {
		$this->user_provider   = $user_provider;
		$this->status_provider = $status_provider;
		$this->request_blocker = $request_blocker;
		$this->policy          = $policy;
	}

	public function register(): void {
		add_filter( 'map_meta_cap', array( $this, 'filter_map_meta_cap' ), 10, 4 );
	}

	public function filter_map_meta_cap( array $caps, string $cap, int $user_id, array $args ): array {
		if ( ! in_array( $cap, array( 'edit_post', 'delete_post' ), true ) ) {
			return $caps;
		}

		$post_id = isset( $args[0] ) ? (int) $args[0] : 0;
		if ( ! $post_id ) {
			return $caps;
		}

		$current_status = $this->status_provider->get_status( $post_id );
		if ( ! $current_status ) {
			return $caps;
		}

		$user_roles = $this->user_provider->get_current_user_roles();

		if ( $this->policy->is_editing_locked( $current_status, $user_roles ) ) {
			if ( $this->request_blocker->is_post_method() ) {
				return array( 'do_not_allow' );
			}
		}
		
		return $caps;
	}
}
