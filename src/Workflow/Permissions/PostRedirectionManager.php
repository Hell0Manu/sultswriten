<?php
/**
 * Gerenciador de Redirecionamentos de Edição.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Permissions;

use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;
use Sults\Writen\Workflow\PostStatus\PostStatusRegistrar;

/**
 * Classe PostRedirectionManager.
 *
 * Responsável por controlar o acesso à tela de edição de posts (`post.php?action=edit`).
 *
 * Diferente do `PostEditingBlocker` (que impede a gravação/salvamento), esta classe
 * impede o acesso visual à interface de administração.
 *
 * Se um usuário restrito tentar acessar a tela de edição de um post bloqueado,
 * ele será redirecionado para a visualização pública (frontend) do post, evitando
 * confusão ou a exibição de uma interface desabilitada.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @author     Sults
 * @since      0.1.0
 */
class PostRedirectionManager {

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
	 * Construtor.
	 *
	 * @param WPUserProviderInterface       $user_provider   Serviço de usuários.
	 * @param WPPostStatusProviderInterface $status_provider Serviço de status.
	 */
	public function __construct(
		WPUserProviderInterface $user_provider,
		WPPostStatusProviderInterface $status_provider
	) {
		$this->user_provider   = $user_provider;
		$this->status_provider = $status_provider;
	}

	/**
	 * Registra o hook de verificação no carregamento da tela de post.
	 *
	 * O hook `load-post.php` é executado especificamente quando a tela de edição
	 * do admin está sendo carregada, mas antes de qualquer HTML ser renderizado.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'load-post.php', array( $this, 'maybe_redirect_from_edit_screen' ) );
		add_action( 'load-edit.php', array( $this, 'redirect_post_list' ) );
	}

	/**
	 * Executa a lógica de redirecionamento.
	 *
	 * Verifica:
	 * 1. Se é uma ação de edição ('edit').
	 * 2. Se o usuário atual pertence a uma role restrita (ex: Redator).
	 * 3. Se o post atual está em um status restrito (ex: Publicado).
	 *
	 * Se todas as condições forem verdadeiras, redireciona para o Permalink.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function maybe_redirect_from_edit_screen(): void {
		// Verificações de segurança básicas para garantir que estamos editando um post.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['post'] ) || ! isset( $_GET['action'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sults_post_id = absint( $_GET['post'] );
		if ( ! $sults_post_id ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );

		if ( 'edit' !== $action ) {
			return;
		}

		$current_status = $this->status_provider->get_status( $sults_post_id );
		$user_roles     = $this->user_provider->get_current_user_roles();

		// Obtém listas de bloqueio via PostStatusRegistrar (com filtros para extensibilidade).
		$statuses_to_block = apply_filters( 'sultswriten_blocked_statuses', PostStatusRegistrar::get_restricted_statuses() );
		$roles_to_block    = apply_filters( 'sultswriten_blocked_roles', PostStatusRegistrar::get_restricted_roles() );

		// Se o usuário tem role bloqueada E o status é bloqueado -> Redireciona.
		if ( array_intersect( $roles_to_block, $user_roles ) && in_array( $current_status, $statuses_to_block, true ) ) {
			wp_safe_redirect( get_permalink( $sults_post_id ) );
			exit;
		}
	}

	/**
	 * Redireciona a listagem padrão de Posts para a página de Estrutura.
	 *
	 * Verifica se a tela atual é a listagem do post type 'post'.
	 * Se for, redireciona para 'admin.php?page=sults-writen-structure'.
	 *
	 * @return void
	 */
	public function redirect_post_list(): void {
		$screen = get_current_screen();

		if ( $screen && 'edit-post' === $screen->id && 'post' === $screen->post_type ) {
			
			$structure_url = admin_url( 'admin.php?page=sults-writen-structure' );
			
			wp_safe_redirect( $structure_url );
			exit;
		}
	}
}