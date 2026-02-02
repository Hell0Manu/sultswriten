<?php
/**
 * Definições de Papéis (Roles) do Sistema.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Permissions;

/**
 * Classe RoleDefinitions.
 *
 * Centraliza os slugs dos papéis de usuário utilizados no plugin.
 *
 * O sistema mapeia os cargos do fluxo editorial para as roles nativas do WordPress
 * para aproveitar as capacidades (capabilities) já existentes, exceto pelo
 * 'Designer', que é um cargo personalizado.
 *
 * Mapeamento:
 * - Redator      -> Contributor (Escreve, mas não publica).
 * - Corretor     -> Author (Pode editar e publicar seus próprios).
 * - Editor Chefe -> Editor (Pode editar e publicar de todos).
 * - Designer     -> Designer (Cargo customizado para fluxo de imagem).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @author     Sults
 * @since      0.1.0
 */
class RoleDefinitions {

	/**
	 * Responsável pela escrita inicial do conteúdo.
	 * Mapeado para 'contributor': Salva rascunhos, mas precisa de aprovação.
	 */
	public const REDATOR = 'contributor';

	/**
	 * Responsável pela revisão ortográfica e gramatical.
	 * Mapeado para 'author': Tem permissão de edição mais elevada.
	 */
	public const CORRETOR = 'author';

	/**
	 * Gerente do fluxo editorial.
	 * Mapeado para 'editor': Acesso total ao conteúdo e moderação.
	 */
	public const EDITOR_CHEFE = 'editor';

	/**
	 * Administrador do sistema técnico.
	 * Mapeado para 'administrator': Acesso total ao WordPress.
	 */
	public const ADMIN = 'administrator';

	/**
	 * Usuário com acesso apenas de leitura (se aplicável).
	 * Mapeado para 'subscriber'.
	 */
	public const VISITANTE = 'subscriber';

	/**
	 * Responsável pela criação de assets visuais.
	 * Role personalizada criada na ativação do plugin.
	 */
	public const DESIGNER = 'designer';
}
