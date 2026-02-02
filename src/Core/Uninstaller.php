<?php
/**
 * Lógica de desinstalação do plugin.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Core
 * @since      0.1.0
 */

namespace Sults\Writen\Core;

/**
 * Classe Uninstaller.
 *
 * Executada apenas quando o usuário clica em "Excluir" no painel de plugins.
 * Este é o momento de remover tabelas do banco de dados, opções (`wp_options`) e
 * arquivos temporários criados pelo plugin.
 *
 * Atualmente não realiza nenhuma operação destrutiva para preservar dados históricos.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Core
 * @author     Sults
 * @since      0.1.0
 */
class Uninstaller {
	/**
	 * Executa a limpeza profunda dos dados do plugin.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function uninstall(): void {
		// Implementar limpeza de banco de dados se necessário no futuro.
	}
}
