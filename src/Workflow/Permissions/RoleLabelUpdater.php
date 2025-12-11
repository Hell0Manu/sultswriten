<?php

namespace Sults\Writen\Workflow\Permissions;

use Sults\Writen\Workflow\Permissions\RoleDefinitions;

/**
 * Responsável por renomear os rótulos dos papéis (roles) do WordPress.
 */
class RoleLabelUpdater {

	public function register(): void {
		add_filter( 'editable_roles', array( $this, 'rename_roles' ) );
	}

	public function rename_roles( array $roles ): array {
		if ( isset( $roles[ RoleDefinitions::EDITOR_CHEFE ] ) ) {
			$roles[ RoleDefinitions::EDITOR_CHEFE ]['name'] = 'Redator-Chefe';
		}

		if ( isset( $roles['contributor'] ) ) {
			$roles['contributor']['name'] = 'Redator';
		}

		if ( isset( $roles[ RoleDefinitions::REDATOR ] ) ) {
			$roles[ RoleDefinitions::REDATOR ]['name'] = 'Corretor';
		}

		if ( isset( $roles[ RoleDefinitions::VISITANTE ] ) ) {
			$roles[ RoleDefinitions::VISITANTE ]['name'] = 'Visitante';
		}

		return $roles;
	}
}
