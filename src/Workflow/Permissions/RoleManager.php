<?php
/**
 * Gerenciador de Permissões e Visibilidade.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Permissions;

use Sults\Writen\Workflow\Permissions\RoleLabelUpdater;
use Sults\Writen\Workflow\Permissions\MediaLibraryLimiter;
use Sults\Writen\Workflow\Permissions\PostListVisibility;
use Sults\Writen\Workflow\Permissions\DeletePrevention;

/**
 * Classe RoleManager.
 *
 * Atua como um agregador para as diversas políticas de permissão do plugin.
 * Em vez de registrar cada regra individualmente no Kernel do plugin,
 * esta classe inicializa todas as regras relacionadas a controle de acesso.
 *
 * Responsabilidades delegadas:
 * 1. Renomear os papéis na interface (RoleLabelUpdater).
 * 2. Restringir a biblioteca de mídia (MediaLibraryLimiter).
 * 3. Restringir a listagem de posts (PostListVisibility).
 * 4. Proteger contra exclusão acidental (DeletePrevention).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @author     Sults
 * @since      0.1.0
 */
class RoleManager {

	/**
	 * Atualizador de nomes de papéis.
	 * @var RoleLabelUpdater
	 */
	private RoleLabelUpdater $label_updater;

	/**
	 * Limitador de acesso à biblioteca de mídia.
	 * @var MediaLibraryLimiter
	 */
	private MediaLibraryLimiter $media_limiter;

	/**
	 * Controlador de visibilidade na lista de posts.
	 * @var PostListVisibility
	 */
	private PostListVisibility $visibility_limiter;

	/**
	 * Prevenção de exclusão de posts.
	 * @var DeletePrevention
	 */
	private DeletePrevention $delete_prevention;

	/**
	 * Construtor.
	 *
	 * Recebe as instâncias das classes de política de segurança.
	 *
	 * @param RoleLabelUpdater    $label_updater      Serviço de renomeação de roles.
	 * @param MediaLibraryLimiter $media_limiter      Serviço de restrição de mídia.
	 * @param PostListVisibility  $visibility_limiter Serviço de filtro de listagem.
	 * @param DeletePrevention    $delete_prevention  Serviço de proteção contra deleção.
	 */
	public function __construct(
		RoleLabelUpdater $label_updater,
		MediaLibraryLimiter $media_limiter,
		PostListVisibility $visibility_limiter,
		DeletePrevention $delete_prevention
	) {
		$this->label_updater      = $label_updater;
		$this->media_limiter      = $media_limiter;
		$this->visibility_limiter = $visibility_limiter;
		$this->delete_prevention  = $delete_prevention;
	}

	/**
	 * Registra os hooks de todos os sub-componentes de permissão.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		$this->label_updater->register();
		$this->media_limiter->register();
		$this->visibility_limiter->register();
		$this->delete_prevention->register();
	}
}