<?php
/**
 * Gerenciador principal do fluxo de status.
 *
 * Esta classe atua como uma Fachada (Facade) para inicializar e coordenar
 * os componentes de registro de status, assets administrativos e
 * apresentação de colunas, garantindo que tudo seja carregado na ordem correta.
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

class StatusManager {

	private PostStatusRegistrar $status_registrar;
	private AdminAssetsManager $assets_manager;
	private PostListPresenter $list_presenter;
	private PostEditingBlocker $editing_blocker;
	private RoleManager $role_manager;
	private NotificationManager $notification_manager;
	private PostRedirectionManager $redirection_manager;

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

	public function register(): void {
		add_action( 'init', array( $this->status_registrar, 'register' ), 5 );
		add_action( 'init', array( $this->editing_blocker, 'register' ), 10 );
		add_action( 'init', array( $this->role_manager, 'register' ), 10 );
		add_action( 'init', array( $this->notification_manager, 'register' ), 10 );
		add_action( 'init', array( $this->redirection_manager, 'register' ), 10 );

		if ( is_admin() ) {
			add_action( 'init', array( $this->assets_manager, 'register' ), 10 );
			add_action( 'init', array( $this->list_presenter, 'register' ), 10 );
		}
	}
}
