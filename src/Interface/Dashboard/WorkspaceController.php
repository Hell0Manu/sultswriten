<?php
/**
 * Controlador do Workspace (Painel Principal).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface\Dashboard
 * @since      0.1.0
 */

namespace Sults\Writen\Interface\Dashboard;

use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\NotificationRepositoryInterface;
use Sults\Writen\Contracts\PostRepositoryInterface;
use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Workflow\WorkflowPolicy;
use Sults\Writen\Workflow\Permissions\RoleDefinitions;

/**
 * Classe WorkspaceController.
 *
 * Responsável por gerenciar a página "Sults Workspace", que serve como a área de trabalho
 * principal para os usuários do fluxo editorial.
 *
 * Funcionalidades:
 * 1. Kanban/Lista de Tarefas: Exibe os posts pertinentes ao usuário atual.
 * 2. Central de Notificações: Exibe alertas de mudança de status e atribuições.
 * 3. Redirecionamento: Força usuários operacionais a irem direto para o Workspace ao logar.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface\Dashboard
 * @author     Sults
 * @since      0.1.0
 */
class WorkspaceController implements HookableInterface {

	/** @var WPUserProviderInterface Acesso a dados do usuário logado. */
	private WPUserProviderInterface $user_provider;

	/** @var NotificationRepositoryInterface Repositório de notificações (leitura/escrita). */
	private NotificationRepositoryInterface $notification_repo;

	/** @var PostRepositoryInterface Repositório de posts (filtro por autor/status). */
	private PostRepositoryInterface $post_repo;

	/** @var WorkflowPolicy Políticas de transição de status. */
	private WorkflowPolicy $policy;

	/** Slug da página no menu do WordPress. */
	public const PAGE_SLUG = 'sults-writen-workspace';

	/**
	 * Construtor.
	 */
	public function __construct(
		WPUserProviderInterface $user_provider,
		NotificationRepositoryInterface $notification_repo,
		PostRepositoryInterface $sults_post_repo,
		WorkflowPolicy $sults_policy
	) {
		$this->user_provider     = $user_provider;
		$this->notification_repo = $notification_repo;
		$this->post_repo         = $sults_post_repo;
		$this->policy            = $sults_policy;
	}

	/**
	 * Registra os hooks do controlador.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		// Adiciona o menu lateral.
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		
		// Processa ações de formulário/URL (ex: limpar notificações) antes do render.
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		
		// Redireciona do dashboard padrão para o Workspace.
		add_action( 'admin_init', array( $this, 'redirect_default_dashboard' ) );
	}

	/**
	 * Redireciona usuários não-admin do painel padrão (index.php) para o Workspace.
	 *
	 * Melhora a experiência do usuário removendo a tela genérica do WP e focando no trabalho.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function redirect_default_dashboard(): void {
		global $sults_pagenow;

		if ( 'index.php' === $sults_pagenow && ! $this->is_admin() ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
			exit;
		}
	}

	/**
	 * Adiciona a página do Workspace ao menu.
	 *
	 * Inclui lógica para exibir um contador de notificações (badge) no título do menu.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function add_menu_page(): void {
		$user_id       = $this->user_provider->get_current_user_id();
		$notifications = $this->notification_repo->get_notifications( $user_id );
		$unread        = $this->count_unread_notifications( $notifications );

		$menu_title = 'Workspace';
		
		// Se houver notificações não lidas, adiciona o badge HTML padrão do WP.
		if ( $unread > 0 ) {
			$menu_title .= sprintf(
				' <span class="update-plugins count-%1$d"><span class="plugin-count">%1$d</span></span>',
				$unread
			);
		}

		add_menu_page(
			'Sults Workspace',   // Título da Página
			$menu_title,         // Título do Menu (com badge)
			'read',              // Capacidade mínima
			self::PAGE_SLUG,     // Slug
			array( $this, 'render' ),
			'dashicons-dashboard', // Ícone
			2                    // Posição (bem no topo)
		);
	}

	/**
	 * Processa ações disparadas via GET (Query String).
	 *
	 * Usado principalmente para gerenciar o estado das notificações (marcar como lida/limpar tudo).
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function handle_actions(): void {
		// Verifica se estamos na página correta.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || 'sults-writen-workspace' !== $_GET['page'] ) {
			return;
		}

		// Verifica se há uma ação solicitada.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['sults_action'] ) ) {
			return;
		}

		// Validação de segurança (Nonce).
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'sults_workspace_action' ) ) {
			return;
		}

		$allowed_actions = array( 'clear_notifs', 'dismiss_notif' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw_action = sanitize_text_field( wp_unslash( $_GET['sults_action'] ) );

		if ( ! in_array( $raw_action, $allowed_actions, true ) ) {
			return;
		}

		$action  = $raw_action;
		$user_id = $this->user_provider->get_current_user_id();

		if ( 'clear_notifs' === $action ) {
			// Limpa todas as notificações do usuário.
			$this->notification_repo->clear_all_notifications( $user_id );
		} elseif ( 'dismiss_notif' === $action ) {
			// Marca uma específica como lida/removida.
			if ( empty( $_GET['notif_id'] ) ) {
				return;
			}
			$notif_id = sanitize_key( wp_unslash( $_GET['notif_id'] ) );
			$this->notification_repo->dismiss_notification( $user_id, $notif_id );
		}

		// Redireciona para limpar a URL (PRG Pattern).
		wp_safe_redirect( remove_query_arg( array( 'sults_action', 'notif_id', '_wpnonce' ) ) );
		exit;
	}

	/**
	 * Renderiza a visualização do Workspace.
	 *
	 * Coleta os dados necessários (Posts, Notificações, Roles) e carrega o template.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function render(): void {
		$user_id = $this->user_provider->get_current_user_id();

		// Busca os posts relevantes para o usuário (Kanban/Lista).
		$my_posts = $this->post_repo->get_posts_for_workspace( $user_id );

		// Busca notificações.
		$notifications = $this->notification_repo->get_notifications( $user_id );
		$unread_count  = $this->count_unread_notifications( $notifications );

		$current_user_roles = $this->user_provider->get_current_user_roles();

		// Carrega a View.
		require __DIR__ . '/views/workspace-home.php';
	}

	/**
	 * Helper para contar notificações não lidas.
	 */
	private function count_unread_notifications( array $notifications ): int {
		return count( array_filter( $notifications, fn( $n ) => empty( $n['read'] ) ) );
	}

	/**
	 * Verifica se o usuário atual é Administrador.
	 */
	private function is_admin(): bool {
		$roles = $this->user_provider->get_current_user_roles();
		return in_array( RoleDefinitions::ADMIN, $roles, true );
	}

	/**
	 * Getter para a política de workflow (pode ser usado na View).
	 */
	public function get_policy(): WorkflowPolicy {
		return $this->policy;
	}
}
