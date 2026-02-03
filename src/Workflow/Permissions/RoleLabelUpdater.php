<?php
/**
 * Atualizador de Rótulos de Papéis (Role Labels).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Permissions;

use Sults\Writen\Workflow\Permissions\RoleDefinitions;

/**
 * Classe RoleLabelUpdater.
 *
 * Responsável por renomear os rótulos dos papéis na interface administrativa do WordPress.
 *
 * O WordPress usa nomes padrão (Editor, Author, Contributor). Esta classe intercepta
 * a lista de papéis editáveis e altera seus nomes de exibição para corresponder
 * à terminologia do fluxo editorial (Redator-Chefe, Corretor, Redator).
 *
 * Importante: Isso altera apenas o "Nome de Exibição", não o "Slug" ou as "Capacidades".
 *
 * @see RoleDefinitions
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @author     Sults
 * @since      0.1.0
 */
class RoleLabelUpdater {

	/**
	 * Registra o filtro para modificar os papéis editáveis.
	 *
	 * O hook `editable_roles` é usado pelo WordPress para popular dropdowns
	 * e listas de seleção de usuários.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		add_filter( 'editable_roles', array( $this, 'rename_roles' ) );
	}

	/**
	 * Renomeia os papéis no array de roles.
	 *
	 * Verifica se os papéis definidos em `RoleDefinitions` existem no array
	 * e atualiza a propriedade `name` deles.
	 *
	 * @since 0.1.0
	 *
	 * @param array $roles Array associativo de papéis e suas propriedades.
	 * @return array O array modificado com os novos nomes.
	 */
	public function rename_roles( array $roles ): array {
		// Editor -> Redator-Chefe
		if ( isset( $roles[ RoleDefinitions::EDITOR_CHEFE ] ) ) {
			$roles[ RoleDefinitions::EDITOR_CHEFE ]['name'] = 'Redator-Chefe';
		}

		// Contributor -> Redator
		if ( isset( $roles[ RoleDefinitions::REDATOR ] ) ) {
			$roles[ RoleDefinitions::REDATOR ]['name'] = 'Redator';
		}

		// Author -> Corretor
		if ( isset( $roles[ RoleDefinitions::CORRETOR ] ) ) {
			$roles[ RoleDefinitions::CORRETOR ]['name'] = 'Corretor';
		}

		// Subscriber -> Visitante
		if ( isset( $roles[ RoleDefinitions::VISITANTE ] ) ) {
			$roles[ RoleDefinitions::VISITANTE ]['name'] = 'Visitante';
		}

		return $roles;
	}

	
}