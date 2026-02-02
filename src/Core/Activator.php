<?php
/**
 * Lógica de ativação do plugin.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Core
 * @since      0.1.0
 */

namespace Sults\Writen\Core;

use Sults\Writen\Workflow\Permissions\RoleCapabilityManager;
use Sults\Writen\Infrastructure\RewriteManager;

/**
 * Classe Activator.
 *
 * Contém os procedimentos estáticos executados quando o plugin é ativado no WordPress.
 *
 * Responsabilidades:
 * 1. Configurar ou atualizar as capacidades (capabilities) dos papéis de usuário (Roles).
 * 2. Limpar as regras de reescrita de URL (flush rewrite rules) para garantir que
 * novas rotas ou Custom Post Types sejam reconhecidos imediatamente.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Core
 * @author     Sults
 * @since      0.1.0
 */
class Activator {

	/**
	 * Executa as tarefas de instalação/ativação.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function activate(): void {
		// Aplica as permissões personalizadas (ex: Redator, Editor).
		$role_manager = new RoleCapabilityManager();
		$role_manager->apply();

		// Reseta os permalinks para evitar erro 404 em novas rotas.
		$rewrite_manager = new RewriteManager();
		$rewrite_manager->flush();
	}
}
