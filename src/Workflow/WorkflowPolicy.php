<?php
/**
 * Políticas de Fluxo de Trabalho (Workflow).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow;

use Sults\Writen\Workflow\PostStatus\StatusConfig;
use Sults\Writen\Workflow\Permissions\RoleDefinitions;

/**
 * Classe WorkflowPolicy.
 *
 * Centraliza as regras de negócio sobre o que é permitido dentro do fluxo editorial.
 * Responde a perguntas como:
 * - "Este usuário pode editar este post neste status?"
 * - "Para quais status este post pode evoluir agora?"
 * - "Qual a cor/label visual deste status?"
 *
 * @see StatusConfig
 * @see RoleDefinitions
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow
 * @author     Sults
 * @since      0.1.0
 */
class WorkflowPolicy {

	/**
	 * Verifica se a edição deve ser bloqueada para o status e roles atuais.
	 *
	 * Regras:
	 * 1. Administradores e Editores-Chefe NUNCA são bloqueados (bypass).
	 * 2. Se o status atual não tiver flag 'is_locked', a edição é livre.
	 * 3. Se estiver bloqueado, verifica se a role do usuário está na lista 'roles_allowed'.
	 *
	 * @since 0.1.0
	 *
	 * @param string $status     Slug do status atual do post.
	 * @param array  $user_roles Lista de roles do usuário atual.
	 * @return bool True se a edição estiver bloqueada (usuário não tem permissão).
	 */
	public function is_editing_locked( string $status, array $user_roles ): bool {
		// Bypass: Admin e Editor Chefe têm poder total.
		if ( in_array( RoleDefinitions::ADMIN, $user_roles, true ) || in_array( RoleDefinitions::EDITOR_CHEFE, $user_roles, true ) ) {
			return false;
		}

		$config = StatusConfig::get_config( $status );
		$rules  = $config['flow_rules'] ?? array();

		// Se não há regra de bloqueio, libera.
		if ( empty( $rules['is_locked'] ) ) {
			return false;
		}

		// Verifica se o usuário tem uma das roles de exceção.
		$allowed_roles  = isset( $rules['roles_allowed'] ) ? $rules['roles_allowed'] : array();
		$has_permission = ! empty( array_intersect( $user_roles, $allowed_roles ) );

		return ! $has_permission;
	}

	/**
	 * Retorna as transições de status permitidas para o usuário atual.
	 *
	 * Filtra os próximos status possíveis baseando-se na role do usuário.
	 * Exemplo: Redatores não podem mover um post para "Suspenso", apenas Admin/Editores podem.
	 *
	 * @since 0.1.0
	 *
	 * @param string $current_status O slug do status atual.
	 * @param array  $user_roles     As roles do usuário.
	 * @return array Lista de slugs de status permitidos como destino.
	 */
	public function get_allowed_transitions( string $current_status, array $user_roles ): array {
		$config = StatusConfig::get_config( $current_status );
		
		$next_statuses = $config['next_statuses'] ?? array();

		// Fallback se não houver configuração explícita.
		if ( empty( $next_statuses ) ) {
			return array( StatusConfig::DRAFT, StatusConfig::PUBLISH );
		}

		// Regra Específica: Redator não pode suspender posts.
		// Se é Redator E NÃO É Admin/Editor.
		if ( in_array( RoleDefinitions::REDATOR, $user_roles ) && ! in_array( RoleDefinitions::ADMIN, $user_roles ) && ! in_array( RoleDefinitions::EDITOR_CHEFE, $user_roles ) ) {
			
			$filtered = array_filter( $next_statuses, function( $status_slug ) {
				return $status_slug !== StatusConfig::SUSPENDED;
			});
			
			return array_values( $filtered ); 
		}

		return $next_statuses;
	}

	/**
	 * Gera o HTML do badge de status para uso em tabelas e listas.
	 *
	 * Cria um elemento visual padronizado (`<span class="sults-status-badge ...">`)
	 * para exibir o status na interface administrativa.
	 *
	 * @since 0.1.0
	 *
	 * @param string $status      O slug do status (ex: 'em-revisao').
	 * @param string $sults_label (Opcional) Label forçado, caso já tenha sido traduzido antes.
	 * @return string HTML seguro do badge.
	 */
	public function get_status_badge( string $status, string $sults_label = '' ): string {
		$config = StatusConfig::get_config( $status );

		$final_label = ! empty( $sults_label ) ? $sults_label : $config['label'];

		// Sanitiza para garantir uma classe CSS válida.
		$css_class = 'sults-status-' . sanitize_html_class( $status );

		return sprintf(
			'<span class="sults-status-badge %s">%s</span>',
			esc_attr( $css_class ),
			esc_html( $final_label )
		);
	}
}
