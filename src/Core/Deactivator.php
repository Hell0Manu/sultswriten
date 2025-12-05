<?php
/**
 * Lógica executada na desativação do plugin.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Core
 * @since      0.1.0
 */

namespace Sults\Writen\Core;

/**
 * Lógica de desativação do plugin.
 */
class Deactivator {

	/**
	 * Executa as tarefas de limpeza ao desativar o plugin.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		self::restore_capabilities();
		flush_rewrite_rules();
	}

	/**
	 * Restaura as capacidades originais dos papéis do WordPress.
	 *
	 * Reverte as alterações feitas na classe Activator.
	 *
	 * @return void
	 */
	private static function restore_capabilities(): void {
		// 1. Redator (Contributor): Remove permissão de upload (volta ao padrão WP)
		$role_contributor = get_role( 'contributor' );
		if ( $role_contributor ) {
			$role_contributor->remove_cap( 'upload_files' );
		}

		// 2. Redator-Chefe (Editor): Devolve permissões de gerenciar Páginas
		$role_editor = get_role( 'editor' );
		if ( $role_editor ) {
			$caps_to_restore = array(
				'edit_pages',
				'publish_pages',
				'delete_pages',
				'delete_published_pages',
				'edit_others_pages',
				'delete_others_pages',
				'read_private_pages',
				'edit_published_pages',
			);
			foreach ( $caps_to_restore as $cap ) {
				$role_editor->add_cap( $cap );
			}
		}

		// 3. Corretor (Author): Devolve permissão de publicar e remove acesso a outros posts
		$role_author = get_role( 'author' );
		if ( $role_author ) {
			// Remove a permissão extra que demos
			$role_author->remove_cap( 'edit_others_posts' );

			// Devolve as permissões que tiramos
			$caps_to_restore = array(
				'create_posts',
				'publish_posts',
				'delete_posts',
				'delete_published_posts',
			);
			foreach ( $caps_to_restore as $cap ) {
				$role_author->add_cap( $cap );
			}
		}
	}
}
