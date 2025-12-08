<?php
/**
 * Lógica executada na desativação do plugin.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Core
 * @since      0.1.0
 */

namespace Sults\Writen\Core;

use Sults\Writen\Workflow\Permissions\RoleCapabilityManager;
use Sults\Writen\Infrastructure\RewriteManager;

class Deactivator {

    public static function deactivate(): void {

		$role_manager = new RoleCapabilityManager();
        $role_manager->revert();

        $rewrite_manager = new RewriteManager();
        $rewrite_manager->flush();
    }
}
