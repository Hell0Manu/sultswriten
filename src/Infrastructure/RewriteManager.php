<?php
/**
 * Gerenciador de regras de reescrita (Rewrite Rules).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */

namespace Sults\Writen\Infrastructure;

/**
 * Classe RewriteManager.
 *
 * Wrapper para a função `flush_rewrite_rules` do WordPress.
 * Responsável por atualizar a estrutura de permalinks.
 *
 * Esta classe é utilizada principalmente pelos ativadores e desativadores (`Activator`/`Deactivator`)
 * para garantir que rotas personalizadas (como Custom Post Types ou Endpoints de API)
 * sejam reconhecidas imediatamente após a instalação do plugin.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @author     Sults
 * @since      0.1.0
 */
class RewriteManager {

	/**
	 * Atualiza as regras de reescrita de URL do WordPress.
	 *
	 * Executa `flush_rewrite_rules()`, que recria a lista de regras de reescrita
	 * e atualiza o arquivo `.htaccess` (se aplicável).
	 *
	 * ATENÇÃO: Esta é uma operação custosa em termos de performance.
	 * Deve ser chamada apenas em eventos pontuais (ativação/desativação/atualização),
	 * nunca no hook `init` ou `wp_loaded` de cada requisição.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function flush(): void {
		flush_rewrite_rules();
	}
}