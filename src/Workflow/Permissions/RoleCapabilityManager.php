<?php

namespace Sults\Writen\Workflow\Permissions;

use Sults\Writen\Workflow\Permissions\RoleDefinitions;

class RoleCapabilityManager {

	/**
	 * Configuração centralizada das permissões.
	 * Define o que adicionar e o que remover para cada papel.
	 */
	private const CAPABILITIES_CONFIG = array(
		RoleDefinitions::REDATOR      => array(
			'add'    => array( 'upload_files' ),
			'remove' => array(),
		),
		RoleDefinitions::EDITOR_CHEFE => array(
			'add'    => array(),
			'remove' => array(
				'edit_pages',
				'publish_pages',
				'delete_pages',
				'delete_published_pages',
				'edit_others_pages',
				'delete_others_pages',
				'read_private_pages',
				'edit_published_pages',
			),
		),
		RoleDefinitions::CORRETOR     => array(
			'add'    => array( 'edit_others_posts' ),
			'remove' => array(
				'publish_posts',
				'delete_posts',
				'delete_published_posts',
			),
		),
		RoleDefinitions::DESIGNER     => array(
			'add'    => array( 'edit_others_posts' ),
			'remove' => array(
				'publish_posts',
				'delete_posts',
				'delete_published_posts',
			),
		),
	);

	/**
	 * Aplica as alterações de capacidade (usado na ativação).
	 */
	public function apply(): void {
		$this->create_custom_roles();
		foreach ( self::CAPABILITIES_CONFIG as $role => $caps ) {
			$this->update_role( $role, $caps['add'], $caps['remove'] );
		}
	}

	/**
	 * Reverte as alterações de capacidade (usado na desativação).
	 * * Truque: Inverte os arrays de 'add' e 'remove' na chamada do update.
	 * O que foi adicionado será removido, e o que foi removido será restaurado.
	 */
	public function revert(): void {
		foreach ( self::CAPABILITIES_CONFIG as $role => $caps ) {
			$this->update_role( $role, $caps['remove'], $caps['add'] );
		}
		remove_role( RoleDefinitions::DESIGNER );
	}

	/**
     * Cria os papéis personalizados se eles não existirem.
     */
	private function create_custom_roles(): void {
			if ( ! get_role( RoleDefinitions::DESIGNER ) ) {
				$base_role = get_role( RoleDefinitions::CORRETOR );
				
				if ( ! $base_role ) {
					// Substituição: Era 'editor'
					$base_role = get_role( RoleDefinitions::EDITOR_CHEFE );
				}

				if ( $base_role ) {
					add_role(
						RoleDefinitions::DESIGNER,
						'Designer', 
						$base_role->capabilities
					);
				}
			}
		}

	/**
	 * Atualiza as capacidades de um papel específico.
	 */
	private function update_role( string $role_slug, array $caps_to_add, array $caps_to_remove ): void {
		$role_obj = get_role( $role_slug );

		if ( ! $role_obj ) {
			return;
		}

		foreach ( $caps_to_add as $cap ) {
			$role_obj->add_cap( $cap );
		}

		foreach ( $caps_to_remove as $cap ) {
			$role_obj->remove_cap( $cap );
		}
	}
}
