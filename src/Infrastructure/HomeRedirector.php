<?php
/**
 * Redirecionador da Página Inicial.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */

namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\HookableInterface;

/**
 * Classe HomeRedirector.
 *
 * Controla o fluxo de acesso à página inicial do site.
 * O objetivo é transformar a home do site em um ponto de entrada para o sistema:
 * - Se logado: Redireciona para o Workspace do plugin.
 * - Se deslogado: Redireciona para a tela de Login.
 *
 * Também inclui proteções para não bloquear requisições internas do WordPress (WP-Cron, etc).
 *
 * @see HookableInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @author     Sults
 * @since      0.1.0
 */
class HomeRedirector implements HookableInterface {

	/**
	 * {@inheritDoc}
	 *
	 * Registra o método de redirecionamento no gancho `template_redirect`.
	 * Este hook roda logo antes do WordPress decidir qual template carregar.
	 */
	public function register(): void {
		add_action( 'template_redirect', array( $this, 'handle_home_redirect' ) );
	}

	/**
	 * Executa a lógica de redirecionamento.
	 *
	 * Verifica se está na página inicial e aplica as regras:
	 * 1. Ignora se o User Agent conter 'WordPress' (requisições de sistema).
	 * 2. Redireciona usuários logados para o painel do plugin.
	 * 3. Redireciona visitantes para o formulário de login.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function handle_home_redirect(): void {
		// Só atua se for a página inicial do site.
		if ( ! is_front_page() ) {
			return;
		}

		// Sanitiza e verifica o User Agent para evitar loops com chamadas internas do WP.
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		if ( strpos( $ua, 'WordPress' ) !== false ) {
			return;
		}

		// Usuário logado vai para o Workspace.
		if ( is_user_logged_in() ) {
			wp_safe_redirect( admin_url( 'admin.php?page=sults-writen-workspace' ) );
			exit;
		}

		// Visitante vai para o Login.
		wp_safe_redirect( wp_login_url() );
		exit;
	}
}