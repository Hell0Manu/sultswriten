<?php

namespace Sults\Writen\Workflow\Permissions;

/**
 * Responsável por renomear os rótulos dos papéis (roles) do WordPress.
 */
class RoleLabelUpdater {

	public function register(): void {
		add_filter( 'editable_roles', array( $this, 'rename_roles' ) );
	}

	public function rename_roles( array $roles ): array {
		if ( isset( $roles['editor'] ) ) {
			$roles['editor']['name'] = 'Redator-Chefe';
		}

		if ( isset( $roles['contributor'] ) ) {
			$roles['contributor']['name'] = 'Redator';
		}

		if ( isset( $roles['author'] ) ) {
			$roles['author']['name'] = 'Corretor';
		}

		if ( isset( $roles['subscriber'] ) ) {
			$roles['subscriber']['name'] = 'Visitante';
		}

		return $roles;
	}
}
