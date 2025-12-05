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
		self::update_capabilities();
		flush_rewrite_rules();
	}

	private static function update_capabilities(): void {
		// 1. Redator (Contributor): Permitir upload de imagens
		$role_contributor = get_role( 'contributor' );
		if ( $role_contributor ) {
			$role_contributor->add_cap( 'upload_files' );
		}

		// 2. Redator-Chefe (Editor): Remover gestão de Páginas (focar em Posts)
		$role_editor = get_role( 'editor' );
		if ( $role_editor ) {
			$caps_to_remove = array(
				'edit_pages',
				'publish_pages',
				'delete_pages',
				'delete_published_pages',
				'edit_others_pages',
				'delete_others_pages',
				'read_private_pages',
				'edit_published_pages',
			);
			foreach ( $caps_to_remove as $cap ) {
				$role_editor->remove_cap( $cap );
			}
		}

		// 3. Corretor (Author): Virar um revisor (Editar outros, mas não publicar)
		$role_author = get_role( 'author' );
		if ( $role_author ) {
			$role_author->add_cap( 'edit_others_posts' );

			$caps_to_remove = array( 'publish_posts', 'delete_posts', 'delete_published_posts' );
			foreach ( $caps_to_remove as $cap ) {
				$role_author->remove_cap( $cap );
			}
		}
	}
}
