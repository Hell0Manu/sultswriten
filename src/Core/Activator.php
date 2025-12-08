<?php
/**
 * Lógica executada na ativação do plugin.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Core
 * @since      0.1.0
 */

namespace Sults\Writen\Core;

use Sults\Writen\Workflow\Permissions\RoleCapabilityManager;
use Sults\Writen\Infrastructure\RewriteManager;

class Activator {

	public static function activate(): void {

		$role_manager = new RoleCapabilityManager();
		$role_manager->apply();

		$rewrite_manager = new RewriteManager();
		$rewrite_manager->flush();
	}
}
