<?php

namespace Sults\Writen\Workflow\Permissions;

use Sults\Writen\Contracts\WPUserProviderInterface;

/**
 * Responsável por restringir a biblioteca de mídia para que redatores vejam apenas seus uploads.
 */
class MediaLibraryLimiter {
	private WPUserProviderInterface $user_provider;

	public function __construct( WPUserProviderInterface $user_provider ) {
		$this->user_provider = $user_provider;
	}

	public function register(): void {
		add_filter( 'ajax_query_attachments_args', array( $this, 'limit_media_library' ) );
	}

	public function limit_media_library( array $query ): array {
		$user_roles = $this->user_provider->get_current_user_roles();
		$user       = wp_get_current_user();

		if ( in_array( 'contributor', $user_roles, true ) ) {
			$query['author'] = $user->ID;
		}

		return $query;
	}
}
