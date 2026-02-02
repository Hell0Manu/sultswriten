<?php
/**
 * Gerenciador principal do fluxo de status e workflow.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow;

use Sults\Writen\Workflow\PostStatus\PostStatusRegistrar;
use Sults\Writen\Workflow\PostStatus\AdminAssetsManager;
use Sults\Writen\Workflow\PostStatus\PostListPresenter;
use Sults\Writen\Workflow\Permissions\PostEditingBlocker;
use Sults\Writen\Workflow\Permissions\RoleManager;
use Sults\Writen\Workflow\Notifications\NotificationManager;
use Sults\Writen\Workflow\Permissions\PostRedirectionManager;
use Sults\Writen\Contracts\HookableInterface;

/**
 * Classe StatusManager.
 *
 * Atua como uma Fachada (Facade) para o módulo de Workflow.
 *
 * Em vez de registrar cada serviço individualmente no Container principal,
 * este gerenciador recebe todas as dependências relacionadas ao fluxo de trabalho
 * e coordena a inicialização delas no hook `init`.
 *
 * Isso garante que:
 * 1. Os status sejam registrados ANTES das regras de permissão (prioridade 5 vs 10).
 * 2. Serviços de UI (Assets, Colunas) sejam carregados apenas no admin.
 *
 * @see HookableInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow
 * @author     Sults
 * @since      0.1.0
 */
class StatusManager implements HookableInterface {

	/**
	 * Serviço de registro de status personalizados.
	 * @var PostStatusRegistrar
	 */
	private PostStatusRegistrar $status_registrar;

	/**
	 * Gerenciador de assets (CSS/JS) do admin.
	 * @var AdminAssetsManager
	 */
	private AdminAssetsManager $assets_manager;

	/**
	 * Apresentador da lista de posts (colunas personalizadas).
	 * @var PostListPresenter
	 */
	private PostListPresenter $list_presenter;

	/**
	 * Bloqueador de edição baseado em status/role.
	 * @var PostEditingBlocker
	 */
	private PostEditingBlocker $editing_blocker;

	/**
	 * Gerenciador de permissões e capacidades.
	 * @var RoleManager
	 */
	private RoleManager $role_manager;

	/**
	 * Gerenciador de notificações de usuário.
	 * @var NotificationManager
	 */
	private NotificationManager $notification_manager;

	/**
	 * Gerenciador de redirecionamentos de workflow.
	 * @var PostRedirectionManager
	 */
	private PostRedirectionManager $redirection_manager;

	/**
	 * Construtor.
	 *
	 * Recebe todas as dependências via DI Container.
	 *
	 * @param PostStatusRegistrar    $status_registrar     Registra os status no WP.
	 * @param AdminAssetsManager     $assets_manager       Carrega estilos das etiquetas de status.
	 * @param PostListPresenter      $list_presenter       Manipula a tabela de listagem de posts.
	 * @param PostEditingBlocker     $editing_blocker      Impede edição de posts travados.
	 * @param RoleManager            $role_manager         Gerencia capabilities dinâmicas.
	 * @param NotificationManager    $notification_manager Envia alertas aos usuários.
	 * @param PostRedirectionManager $redirection_manager  Redireciona acessos indevidos.
	 */
	public function __construct(
		PostStatusRegistrar $status_registrar,
		AdminAssetsManager $assets_manager,
		PostListPresenter $list_presenter,
		PostEditingBlocker $editing_blocker,
		RoleManager $role_manager,
		NotificationManager $notification_manager,
		PostRedirectionManager $redirection_manager
	) {
		$this->status_registrar     = $status_registrar;
		$this->assets_manager       = $assets_manager;
		$this->list_presenter       = $list_presenter;
		$this->editing_blocker      = $editing_blocker;
		$this->role_manager         = $role_manager;
		$this->notification_manager = $notification_manager;
		$this->redirection_manager  = $redirection_manager;
	}

	/**
	 * Registra os hooks de todos os subsistemas de workflow.
	 *
	 * Define a ordem de execução crítica:
	 * 1. `PostStatusRegistrar` roda em prioridade 5 para garantir que os status existam
	 * antes que outros componentes tentem usá-los.
	 * 2. Componentes de UI (Assets, ListPresenter) são registrados apenas no Admin (`is_admin()`).
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		// Prioridade 5: Registrar status primeiro.
		add_action( 'init', array( $this->status_registrar, 'register' ), 5 );

		// Prioridade 10: Lógica de negócios e permissões.
		add_action( 'init', array( $this->editing_blocker, 'register' ), 10 );
		add_action( 'init', array( $this->role_manager, 'register' ), 10 );
		add_action( 'init', array( $this->notification_manager, 'register' ), 10 );
		add_action( 'init', array( $this->redirection_manager, 'register' ), 10 );

		// Componentes visuais apenas no painel administrativo.
		if ( is_admin() ) {
			add_action( 'init', array( $this->assets_manager, 'register' ), 10 );
			add_action( 'init', array( $this->list_presenter, 'register' ), 10 );
		}
	}
}