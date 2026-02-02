<?php
/**
 * Redirecionador de Páginas Não Encontradas (404).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */

namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\HookableInterface;

/**
 * Classe NotFoundRedirector.
 *
 * Responsável por interceptar acessos a páginas inexistentes (Erro 404)
 * e redirecionar o usuário de volta para o Workspace (Painel Principal).
 *
 * Isso evita que usuários se percam em páginas de erro do tema e garante
 * que o fluxo de trabalho permaneça dentro da interface do plugin.
 *
 * @see HookableInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @author     Sults
 * @since      0.1.0
 */
class NotFoundRedirector implements HookableInterface {

	/**
	 * {@inheritDoc}
	 *
	 * Registra o hook `template_redirect` para verificar erros 404 antes
	 * do WordPress renderizar o template do tema.
	 */
	public function register(): void {
		add_action( 'template_redirect', array( $this, 'handle_404_redirect' ) );
	}

	/**
	 * Trata o redirecionamento de erro 404.
	 *
	 * Se a query atual resultou em um 404, redireciona imediatamente
	 * para a página do Workspace (`sults-writen-workspace`).
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function handle_404_redirect(): void {
		if ( ! is_404() ) {
			return;
		}

		// Redireciona para o painel principal do plugin.
		wp_safe_redirect( admin_url( 'admin.php?page=sults-writen-workspace' ) );
		exit;
	}
}