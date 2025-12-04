<?php
/**
 * Lógica executada na desativação do plugin.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Core
 * @since      0.1.0
 */

namespace Sults\Writen\Core;

class Deactivator {

	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}