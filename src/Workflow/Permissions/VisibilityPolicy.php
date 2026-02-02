<?php
/**
 * Política de Visibilidade de Conteúdo.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Permissions;

use Sults\Writen\Contracts\WPUserProviderInterface;

/**
 * Classe VisibilityPolicy.
 *
 * Centraliza as decisões lógicas sobre o escopo de visão dos usuários.
 * Responde a perguntas como:
 * - "O usuário X pode ver posts de outros autores?"
 * - "Quais status são considerados públicos para a equipe interna?"
 *
 * É utilizada principalmente por `PostListVisibility` para aplicar filtros no banco de dados.
 *
 * @see PostListVisibility
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @author     Sults
 * @since      0.1.0
 */
class VisibilityPolicy {

	/**
	 * Provedor de dados do usuário.
	 * @var WPUserProviderInterface
	 */
	private WPUserProviderInterface $user_provider;

	/**
	 * Construtor.
	 *
	 * @param WPUserProviderInterface $user_provider Serviço para recuperar roles.
	 */
	public function __construct( WPUserProviderInterface $user_provider ) {
		$this->user_provider = $user_provider;
	}

	/**
	 * Determina se o usuário atual tem permissão para ver posts de outros autores.
	 *
	 * Regra:
	 * - Redatores: NÃO (False). Eles devem focar apenas no próprio trabalho para evitar distrações.
	 * - Outros (Editores, Corretores, Admin): SIM (True). Precisam de visão geral.
	 *
	 * @since 0.1.0
	 * @return bool True se a visão for irrestrita, False se deve ser filtrada.
	 */
	public function can_see_others_posts(): bool {
		$roles = $this->user_provider->get_current_user_roles();

		// Se for Redator, bloqueia a visão de terceiros.
		if ( in_array( RoleDefinitions::REDATOR, $roles, true ) ) {
			return false;
		}
		
		return true;
	}

	/**
	 * Retorna a lista de status que usuários restritos PODEM ver, mesmo não sendo autores.
	 *
	 * Isso permite que redatores consultem posts já finalizados ou publicados
	 * para usar como referência de estilo/conteúdo, sem ver os rascunhos em andamento
	 * dos colegas.
	 *
	 * @since 0.1.0
	 * @return array Lista de slugs de status.
	 */
	public function get_allowed_statuses_for_restricted_user(): array {
		// Permite ver o que já está publicado ou finalizado.
		return array( 'publish', 'finished' );
	}
}