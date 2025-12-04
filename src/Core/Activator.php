<?php
/**
 * Lógica executada na ativação do plugin.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Core
 * @since      0.1.0
 */

namespace Sults\Writen\Core;

class Activator {

	public static function activate(): void {
		flush_rewrite_rules();
	}
}