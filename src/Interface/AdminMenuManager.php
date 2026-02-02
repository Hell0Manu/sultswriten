<?php
/**
 * Gerenciador de Menus do Painel Administrativo.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface
 * @since      0.1.0
 */

namespace Sults\Writen\Interface;

use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Workflow\Permissions\RoleDefinitions;

/**
 * Classe AdminMenuManager.
 *
 * Responsável por simplificar a interface do WordPress para usuários não administradores.
 *
 * Implementa uma estratégia de "Allowlist" (Lista de Permissão) para os menus:
 * Para qualquer usuário que NÃO seja Administrador (ex: Redatores, Editores),
 * remove todos os itens do menu, exceto os estritamente necessários para o trabalho
 * (Posts, Mídia, Perfil e páginas do plugin).
 *
 * Isso reduz a carga cognitiva, evita distrações e impede acesso visual a configurações
 * que não deveriam ser tocadas.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface
 * @author     Sults
 * @since      0.1.0
 */
class AdminMenuManager implements HookableInterface {

	/**
	 * Provedor de dados do usuário atual.
	 * @var WPUserProviderInterface
	 */
	private WPUserProviderInterface $user_provider;

	/**
	 * Lista de slugs de menu permitidos para não-admins.
	 *
	 * Inclui:
	 * - Páginas personalizadas do plugin (Workspace, Structure).
	 * - Fluxo de edição (edit.php, post-new.php).
	 * - Uploads e Perfil.
	 *
	 * @var array<string>
	 */
	private const ALLOWED_PAGES = array(
		'sults-writen-workspace', // Painel de Tarefas
		'sults-writen-structure', // Configuração de Estrutura (se aplicável)
		'edit.php',               // Listagem de Posts
		'post-new.php',           // Novo Post
		'post.php',               // Edição de Post
		'upload.php',             // Biblioteca de Mídia
		'profile.php',            // Perfil do Usuário
	);

	/**
	 * Construtor.
	 *
	 * @param WPUserProviderInterface $user_provider Serviço de usuário.
	 */
	public function __construct( WPUserProviderInterface $user_provider ) {
		$this->user_provider = $user_provider;
	}

	/**
	 * Registra as ações de limpeza de menu.
	 *
	 * Utiliza prioridade 999 para garantir que roda DEPOIS que outros plugins
	 * adicionaram seus menus, permitindo removê-los efetivamente.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'cleanup_menus' ), 999 );
	}

	/**
	 * Executa a lógica de limpeza baseada na role do usuário.
	 *
	 * 1. Remove sempre o menu de Comentários (não usado no workflow).
	 * 2. Se for Admin, encerra (vê tudo).
	 * 3. Se não for Admin, aplica a restrição severa (Allowlist).
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function cleanup_menus(): void {
		// Remove Comentários globalmente (mesmo para admin, se desejado, ou ajuste aqui).
		remove_menu_page( 'edit-comments.php' );

		$roles = $this->user_provider->get_current_user_roles();

		// Administradores têm acesso total.
		if ( in_array( RoleDefinitions::ADMIN, $roles, true ) ) {
			return;
		}

		$this->apply_allowlist( self::ALLOWED_PAGES );
	}

	/**
	 * Remove todos os menus que não estão na lista permitida.
	 *
	 * @param array $allowed_slugs Lista de slugs permitidos.
	 */
	private function apply_allowlist( array $allowed_slugs ): void {
		global $menu;

		if ( empty( $menu ) ) {
			return;
		}

		foreach ( $menu as $index => $item ) {
			// O slug da página é o terceiro elemento do array de menu do WP.
			$slug = $item[2];

			// Mantém os separadores visuais do WordPress.
			if ( false !== strpos( $slug, 'separator' ) ) {
				continue;
			}

			// Se não estiver na lista permitida, remove.
			if ( ! in_array( $slug, $allowed_slugs, true ) ) {
				remove_menu_page( $slug );
			}
		}

		// Garante a remoção do Dashboard (index.php) se não estiver explícito na lista.
		// Geralmente queremos redirecionar o usuário para o Workspace em vez do Dashboard padrão.
		if ( ! in_array( 'index.php', $allowed_slugs, true ) ) {
			remove_menu_page( 'index.php' );
		}
	}
}