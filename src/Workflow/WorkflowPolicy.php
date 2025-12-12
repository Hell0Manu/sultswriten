<?php
namespace Sults\Writen\Workflow;

use Sults\Writen\Workflow\PostStatus\StatusConfig;
use Sults\Writen\Workflow\Permissions\RoleDefinitions;

class WorkflowPolicy {
	/**
	 * Verifica se a edição deve ser bloqueada para o status e roles atuais.
	 *
	 * @param string $status Slug do status atual do post.
	 * @param array  $user_roles Roles do usuário atual.
	 * @return bool True se a edição estiver bloqueada.
	 */
	public function is_editing_locked( string $status, array $user_roles ): bool {
		if ( in_array( RoleDefinitions::ADMIN, $user_roles, true ) ) {
			return false;
		}

		$config = StatusConfig::get_config( $status );
		$rules  = $config['flow_rules'];

		if ( empty( $rules['is_locked'] ) ) {
			return false;
		}

		$allowed_roles  = isset( $rules['roles_allowed'] ) ? $rules['roles_allowed'] : array();
		$has_permission = ! empty( array_intersect( $user_roles, $allowed_roles ) );

		return ! $has_permission;
	}

	/**
	 * Gera o HTML do badge de status para uso em tabelas e listas.
	 *
	 * @param string $status O slug do status.
	 * @param string $label (Opcional) Label forçado, se já tiver sido buscado antes.
	 * @return string HTML do span.
	 */
	public function get_status_badge( string $status, string $label = '' ): string {
		$config = StatusConfig::get_config( $status );

		$final_label = ! empty( $label ) ? $label : $config['label'];
		$css_class   = $config['css_class'];

		return sprintf(
			'<span class="sults-status-badge %s">%s</span>',
			esc_attr( $css_class ),
			esc_html( $final_label )
		);
	}
}
