<?php

/**
 * Gerencia as permissões, capacidades e visibilidade dos usuários no Workflow.
 *
 * Responsável por:
 * 1. Renomear os papéis (labels) na interface (Ex: Contributor -> Redator).
 * 2. Restringir a biblioteca de mídia para que redatores vejam apenas seus uploads.
 * 3. Restringir a listagem de posts para que redatores não vejam rascunhos de outros.
 * 4. Impedir a exclusão permanente de posts por não-admins.
 */

namespace Sults\Writen\Workflow\Permissions;

use Sults\Writen\Workflow\Permissions\RoleLabelUpdater;
use Sults\Writen\Workflow\Permissions\MediaLibraryLimiter;
use Sults\Writen\Workflow\Permissions\PostListVisibility;
use Sults\Writen\Workflow\Permissions\DeletePrevention;

class RoleManager {
    private RoleLabelUpdater $label_updater;
    private MediaLibraryLimiter $media_limiter;
    private PostListVisibility $visibility_limiter;
    private DeletePrevention $delete_prevention;

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

    public function register(): void {
        $this->label_updater->register();
        $this->media_limiter->register();
        $this->visibility_limiter->register();
        $this->delete_prevention->register();
    }
}
