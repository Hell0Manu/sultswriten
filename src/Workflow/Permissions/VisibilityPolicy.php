<?php
namespace Sults\Writen\Workflow\Permissions;

use Sults\Writen\Contracts\WPUserProviderInterface;

class VisibilityPolicy {
    private WPUserProviderInterface $user_provider;

    public function __construct( WPUserProviderInterface $user_provider ) {
        $this->user_provider = $user_provider;
    }

    public function can_see_others_posts(): bool {
        $roles = $this->user_provider->get_current_user_roles();

        if ( in_array( RoleDefinitions::REDATOR, $roles, true ) ) {
            return false;
        }
        return true;
    }
    
    public function get_allowed_statuses_for_restricted_user(): array {
        return array( 'publish', 'finished' );
    }
}