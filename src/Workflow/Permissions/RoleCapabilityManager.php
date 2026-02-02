<?php
/**
 * Gerenciador de Capacidades (Capabilities) dos Papéis.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Permissions;

use Sults\Writen\Workflow\Permissions\RoleDefinitions;

/**
 * Classe RoleCapabilityManager.
 *
 * Responsável por configurar granularmente o que cada papel pode ou não fazer no WordPress.
 *
 * Diferente do `RoleManager` (que lida com lógica de tempo de execução), esta classe
 * é focada na manipulação persistente das regras de acesso no banco de dados.
 * Geralmente é invocada apenas na ativação e desativação do plugin.
 *
 * Alterações principais:
 * - Redator: Ganha permissão de upload.
 * - Editor Chefe: Perde permissão de mexer em Páginas (foca apenas em Posts).
 * - Corretor: Ganha permissão de editar posts de outros, mas perde poder de publicar/deletar.
 * - Designer: Criação de um novo papel baseado no Corretor.
 *
 * @see RoleDefinitions
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @author     Sults
 * @since      0.1.0
 */
class RoleCapabilityManager {

	/**
	 * Configuração centralizada das permissões.
	 *
	 * Mapa estático definindo as capacidades a serem adicionadas ('add')
	 * e removidas ('remove') para cada papel.
	 *
	 * @var array
	 */
	private const CAPABILITIES_CONFIG = array(
		RoleDefinitions::REDATOR      => array(
			'add'    => array( 'upload_files' ), // Necessário para enviar imagens de rascunho.
			'remove' => array(),
		),
		RoleDefinitions::EDITOR_CHEFE => array(
			'add'    => array(),
			'remove' => array(
				// Remove acesso a Páginas estáticas para focar no fluxo de Posts.
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
			'add'    => array( 'edit_others_posts' ), // Precisa editar posts de redatores.
			'remove' => array(
				'publish_posts',          // Corretores não publicam.
				'delete_posts',           // Corretores não deletam.
				'delete_published_posts',
			),
		),
		RoleDefinitions::DESIGNER     => array(
			'add'    => array( 'edit_others_posts' ), // Precisa inserir imagens em posts alheios.
			'remove' => array(
				'publish_posts',
				'delete_posts',
				'delete_published_posts',
			),
		),
	);

	/**
	 * Aplica as alterações de capacidade no banco de dados.
	 *
	 * Deve ser chamado na ativação do plugin.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function apply(): void {
		$this->create_custom_roles();
		foreach ( self::CAPABILITIES_CONFIG as $role => $caps ) {
			$this->update_role( $role, $caps['add'], $caps['remove'] );
		}
	}

	/**
	 * Reverte as alterações de capacidade para o padrão do WordPress.
	 *
	 * Deve ser chamado na desativação do plugin.
	 *
	 * Lógica de Reversão: O que foi adicionado é removido, e o que foi removido é readicionado.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function revert(): void {
		foreach ( self::CAPABILITIES_CONFIG as $role => $caps ) {
			// Inverte a ordem: remove o que foi add, adiciona o que foi remove.
			$this->update_role( $role, $caps['remove'], $caps['add'] );
		}
		// Remove o papel customizado criado pelo plugin.
		remove_role( RoleDefinitions::DESIGNER );
	}

	/**
	 * Cria os papéis personalizados se eles não existirem.
	 *
	 * Clona as capacidades de um papel existente (Author/Editor) para criar a base
	 * do papel 'Designer'.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private function create_custom_roles(): void {
		if ( ! get_role( RoleDefinitions::DESIGNER ) ) {
			// Tenta clonar do 'Author' (Corretor).
			$base_role = get_role( RoleDefinitions::CORRETOR );

			// Fallback para 'Editor' se 'Author' não existir.
			if ( ! $base_role ) {
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
	 *
	 * Wrapper para os métodos `add_cap` e `remove_cap` do objeto WP_Role.
	 *
	 * @since 0.1.0
	 * @param string $role_slug      O slug do papel a ser editado.
	 * @param array  $caps_to_add    Lista de capabilities para conceder.
	 * @param array  $caps_to_remove Lista de capabilities para revogar.
	 * @return void
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