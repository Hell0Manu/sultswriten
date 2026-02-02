<?php
/**
 * Bloqueador de Edição Baseado em Status.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Permissions;

use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;
use Sults\Writen\Contracts\RequestProviderInterface;
use Sults\Writen\Workflow\WorkflowPolicy;

/**
 * Classe PostEditingBlocker.
 *
 * Responsável por interceptar a verificação de permissões do WordPress (`current_user_can`)
 * e negar o acesso de edição/exclusão se as regras de Workflow assim determinarem.
 *
 * Funciona em conjunto com a `WorkflowPolicy` para garantir que, por exemplo,
 * um Redator não consiga alterar um post que já está "Em Revisão".
 *
 * @see WorkflowPolicy
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @author     Sults
 * @since      0.1.0
 */
class PostEditingBlocker {

	/**
	 * Provedor de dados do usuário.
	 * @var WPUserProviderInterface
	 */
	private WPUserProviderInterface $user_provider;

	/**
	 * Provedor de status do post.
	 * @var WPPostStatusProviderInterface
	 */
	private WPPostStatusProviderInterface $status_provider;

	/**
	 * Provedor de dados da requisição HTTP.
	 * @var RequestProviderInterface
	 */
	private RequestProviderInterface $request_provider;

	/**
	 * Política de regras de negócio.
	 * @var WorkflowPolicy
	 */
	private WorkflowPolicy $policy;

	/**
	 * Construtor.
	 *
	 * @param WPUserProviderInterface       $user_provider    Serviço de usuários.
	 * @param WPPostStatusProviderInterface $status_provider  Serviço de status.
	 * @param RequestProviderInterface      $request_provider Serviço de request.
	 * @param WorkflowPolicy                $sults_policy     Regras de bloqueio.
	 */
	public function __construct(
		WPUserProviderInterface $user_provider,
		WPPostStatusProviderInterface $status_provider,
		RequestProviderInterface $request_provider,
		WorkflowPolicy $sults_policy
	) {
		$this->user_provider    = $user_provider;
		$this->status_provider  = $status_provider;
		$this->request_provider = $request_provider;
		$this->policy           = $sults_policy;
	}

	/**
	 * Registra o filtro de mapeamento de capacidades.
	 *
	 * O hook `map_meta_cap` é o ponto final de decisão do WordPress sobre
	 * se um usuário pode ou não realizar uma ação específica em um objeto específico.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		add_filter( 'map_meta_cap', array( $this, 'filter_map_meta_cap' ), 10, 4 );
	}

	/**
	 * Filtra as capacidades requeridas (Meta Capabilities).
	 *
	 * Lógica de Bloqueio:
	 * 1. Verifica se a ação é editar ou deletar post.
	 * 2. Verifica se o post tem um status definido.
	 * 3. Consulta a `WorkflowPolicy` para saber se aquele status está bloqueado para a role do usuário.
	 * 4. Se bloqueado E a requisição for POST (tentativa de salvar), nega a permissão.
	 *
	 * Nota: Ao checar `is_post_method`, permitimos que o usuário ACESSE a tela de edição (GET),
	 * funcionando como um modo "Somente Leitura", mas bloqueamos o SALVAMENTO.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $caps    As capacidades primitivas requeridas (ex: ['edit_others_posts']).
	 * @param string $cap     A meta capacidade sendo verificada (ex: 'edit_post').
	 * @param int    $user_id O ID do usuário.
	 * @param array  $args    Argumentos adicionais (geralmente [0] => post_id).
	 * @return array Array modificado de capacidades. Se retornar ['do_not_allow'], o acesso é negado.
	 */
	public function filter_map_meta_cap( array $caps, string $cap, int $user_id, array $args ): array {
		// Só nos importamos com edição e exclusão.
		if ( ! in_array( $cap, array( 'edit_post', 'delete_post' ), true ) ) {
			return $caps;
		}

		// Precisamos do ID do post para verificar o status.
		$sults_post_id = isset( $args[0] ) ? (int) $args[0] : 0;
		if ( ! $sults_post_id ) {
			return $caps;
		}

		$current_status = $this->status_provider->get_status( $sults_post_id );
		if ( ! $current_status ) {
			return $caps;
		}

		$user_roles = $this->user_provider->get_current_user_roles();

		// Se a política diz que está bloqueado para este usuário neste status.
		if ( $this->policy->is_editing_locked( $current_status, $user_roles ) ) {
			// Bloqueia efetivamente apenas na tentativa de gravação (POST).
			// Isso evita telas de "Acesso Negado" ao apenas tentar visualizar o post no admin.
			if ( $this->request_provider->is_post_method() ) {
				return array( 'do_not_allow' );
			}
		}

		return $caps;
	}
}