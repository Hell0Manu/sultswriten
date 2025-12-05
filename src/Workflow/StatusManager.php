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

class StatusManager {

	private PostStatusRegistrar $status_registrar;
	private AdminAssetsManager $assets_manager;
	private PostListPresenter $list_presenter;
	private PostEditingBlocker $editing_blocker;
	private RoleManager $role_manager;

	public function __construct(
		PostStatusRegistrar $status_registrar,
		AdminAssetsManager $assets_manager,
		PostListPresenter $list_presenter,
		PostEditingBlocker $editing_blocker,
		RoleManager $role_manager
	) {
		$this->status_registrar = $status_registrar;
		$this->assets_manager   = $assets_manager;
		$this->list_presenter   = $list_presenter;
		$this->editing_blocker  = $editing_blocker;
		$this->role_manager     = $role_manager;
	}

	public function register(): void {
		$this->status_registrar->register();
		$this->editing_blocker->register();
		$this->role_manager->register();

		if ( is_admin() ) {
			$this->assets_manager->register();
			$this->list_presenter->register();
		}
	}
}
