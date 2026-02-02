<?php
/**
 * Lógica de desativação do plugin.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Core
 * @since      0.1.0
 */

namespace Sults\Writen\Core;

use Sults\Writen\Workflow\Permissions\RoleCapabilityManager;
use Sults\Writen\Infrastructure\RewriteManager;

/**
 * Classe Deactivator.
 *
 * Contém os procedimentos estáticos executados quando o plugin é desativado.
 *
 * Responsabilidades:
 * 1. Reverter as capacidades dos usuários para o estado original (remove roles criadas).
 * 2. Limpar regras de reescrita para remover rotas que não existem mais.
 *
 * Nota: Desativar é diferente de Desinstalar. Dados de posts geralmente são mantidos aqui.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Core
 * @author     Sults
 * @since      0.1.0
 */
class Deactivator {

	/**
	 * Executa as tarefas de limpeza na desativação.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function deactivate(): void {
	    // Remove as permissões exclusivas do plugin.
		$role_manager = new RoleCapabilityManager();
		$role_manager->revert();

		// Limpa os permalinks.
		$rewrite_manager = new RewriteManager();
		$rewrite_manager->flush();
	}
}
