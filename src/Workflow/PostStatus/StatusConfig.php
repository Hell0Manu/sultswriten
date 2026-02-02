<?php
/**
 * Configuração dos Status do Workflow.
 *
 * Define todos os estados possíveis do post, suas regras de transição e permissões.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\PostStatus
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\PostStatus;

use Sults\Writen\Workflow\Permissions\RoleDefinitions;

/**
 * Classe StatusConfig.
 *
 * Responsável por centralizar as definições de todos os status do plugin.
 *
 * Funciona como uma "Máquina de Estados" (State Machine), onde cada status define:
 * 1. Suas propriedades de registro no WordPress (`wp_args`).
 * 2. Quem pode editar o post neste estado (`flow_rules`).
 * 3. Para quais estados o post pode ir a partir daqui (`next_statuses`).
 *
 * @see RoleDefinitions
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\PostStatus
 * @author     Sults
 * @since      0.1.0
 */
class StatusConfig {

	// --- STATUS NATIVOS DO WP ---
	public const DRAFT   = 'draft';
	public const PENDING = 'pending';
	public const PUBLISH = 'publish';

	// --- STATUS DE FLUXO DE TEXTO ---
	/** @var string Redator escrevendo. */
	public const TEXT_IN_PROGRESS = 'text_in_progress';
	/** @var string Corretor revisando. */
	public const TEXT_REVIEW      = 'text_review';
	/** @var string Devolvido para ajustes. */
	public const TEXT_ADJUSTMENT  = 'text_adjustment';

	// --- STATUS DE FLUXO DE IMAGEM ---
	/** @var string Aguardando Designer iniciar. */
	public const PENDING_IMAGE     = 'pending_image';
	/** @var string Designer criando assets. */
	public const IMAGE_IN_PROGRESS = 'image_in_progress';
	/** @var string Editor validando design. */
	public const IMAGE_REVIEW      = 'image_review';
	/** @var string Devolvido para ajustes de design. */
	public const IMAGE_ADJUSTMENT  = 'image_adjustment';

	// --- STATUS FINAIS ---
	/** @var string Pronto para publicar. */
	public const PENDING_PUBLICATION = 'pending_pub';
	/** @var string Pausado/Arquivado. */
	public const SUSPENDED           = 'suspended';

	/**
	 * Retorna a configuração completa de todos os status.
	 *
	 * Estrutura do array de configuração:
	 * - `label`: Nome legível do status.
	 * - `wp_args`: (Opcional) Argumentos para `register_post_status`.
	 * - `flow_rules`:
	 * - `is_locked`: Se true, impede edição exceto para roles permitidas.
	 * - `roles_allowed`: Lista de roles que podem editar quando travado.
	 * - `next_statuses`: Array de slugs permitidos para a próxima etapa.
	 *
	 * @since 0.1.0
	 * @return array Mapa de configurações.
	 */
	public static function get_all(): array {
		return array(
			// --- FLUXO INICIAL ---
			self::DRAFT => array(
				'label'       => 'Rascunho',
				'flow_rules'  => array(
					'is_locked'     => false,
					'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE, RoleDefinitions::REDATOR ),
				),
				'next_statuses' => array( self::TEXT_IN_PROGRESS, self::TEXT_REVIEW, self::SUSPENDED ),
			),

			// --- FLUXO DE TEXTO ---
			self::TEXT_IN_PROGRESS => array( 
				'label'       => 'Texto em andamento',
				'wp_args'     => self::get_default_args(),
				'flow_rules'  => array(
					'is_locked'     => false,
					'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE, RoleDefinitions::REDATOR ),
				),
				'next_statuses' => array( self::TEXT_REVIEW, self::TEXT_IN_PROGRESS, self::SUSPENDED ),
			),

			self::TEXT_REVIEW => array(
				'label'       => 'Revisão de texto',
				'wp_args'     => self::get_default_args(),
				'flow_rules'  => array(
					'is_locked'     => true, // Bloqueia para Redator
					'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE, RoleDefinitions::CORRETOR ),
				),
				'next_statuses' => array( self::TEXT_ADJUSTMENT, self::PENDING_IMAGE, self::SUSPENDED ),
			),

			self::TEXT_ADJUSTMENT => array(
				'label'       => 'Ajustar texto',
				'wp_args'     => self::get_default_args(),
				'flow_rules'  => array(
					'is_locked'     => false,
					'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE, RoleDefinitions::REDATOR ),
				),
				'next_statuses' => array( self::TEXT_IN_PROGRESS, self::TEXT_REVIEW, self::SUSPENDED ),
			),

			// --- FLUXO DE IMAGEM ---
			self::PENDING_IMAGE => array(
				'label'       => 'Aguardando imagem',
				'wp_args'     => self::get_default_args(),
				'flow_rules'  => array(
					'is_locked'     => true,
					'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE, RoleDefinitions::DESIGNER ),
				),
				'next_statuses' => array( self::PENDING_IMAGE, self::IMAGE_IN_PROGRESS, self::SUSPENDED ),
			),

			self::IMAGE_IN_PROGRESS => array(
				'label'       => 'Imagem em andamento',
				'wp_args'     => self::get_default_args(),
				'flow_rules'  => array(
					'is_locked'     => false,
					'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE, RoleDefinitions::DESIGNER ),
				),
				'next_statuses' => array( self::IMAGE_IN_PROGRESS, self::IMAGE_REVIEW, self::SUSPENDED ),
			),

			self::IMAGE_REVIEW => array(
				'label'       => 'Revisão de imagem',
				'wp_args'     => self::get_default_args(),
				'flow_rules'  => array(
					'is_locked'     => true,
					'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE ),
				),
				'next_statuses' => array( self::IMAGE_ADJUSTMENT, self::PENDING_PUBLICATION, self::SUSPENDED ),
			),

			self::IMAGE_ADJUSTMENT => array(
				'label'       => 'Ajustar imagem',
				'wp_args'     => self::get_default_args(),
				'flow_rules'  => array(
					'is_locked'     => false,
					'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE ),
				),
				'next_statuses' => array( self::IMAGE_ADJUSTMENT, self::IMAGE_IN_PROGRESS, self::SUSPENDED ),
			),
			
			// --- FLUXO FINAL ---
			self::PENDING_PUBLICATION => array(
				'label'       => 'Aguardando publicação',
				'wp_args'     => self::get_default_args(),
				'flow_rules'  => array(
					'is_locked'     => true,
					'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE ),
				),
				'next_statuses' => array( self::PENDING_PUBLICATION, self::PUBLISH, self::SUSPENDED ),
			),

			self::PUBLISH => array(
				'label'       => 'Publicado',
				'flow_rules'  => array(
					'is_locked'     => true, // Publicado geralmente é imutável no fluxo
					'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE ),
				),
				'next_statuses' => array( self::SUSPENDED, self::DRAFT ), 
			),

			self::SUSPENDED => array(
				'label'       => 'Suspenso',
				'wp_args'     => self::get_default_args( true ),
				'flow_rules'  => array(
					'is_locked'     => true,
					'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE ),
				),
				'next_statuses' => array( self::DRAFT ),
			),
		);
	}
	
	/**
	 * Retorna os argumentos padrão para registro de status no WordPress.
	 *
	 * Configura o status para ser interno (não visível no front para visitantes),
	 * mas visível em todas as listas administrativas.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $protected Se true, define o status como protegido.
	 * @return array Argumentos para register_post_status.
	 */
	private static function get_default_args( bool $protected = false ): array {
		return array(
			'public'                    => false,
			'internal'                  => false,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'protected'                 => $protected,
		);
	}

	/**
	 * Recupera a configuração de um status específico.
	 *
	 * Se o slug não for encontrado nas definições do plugin, tenta buscar
	 * o objeto de status nativo do WP para retornar ao menos o label correto.
	 *
	 * @since 0.1.0
	 *
	 * @param string $slug O slug do status desejado.
	 * @return array Configuração do status (label, flow_rules, next_statuses).
	 */
	public static function get_config( string $slug ): array {
		$all = self::get_all();
		if ( isset( $all[ $slug ] ) ) {
			return $all[ $slug ];
		}
		
		// Fallback para status nativos ou desconhecidos.
		return array(
			'label'         => get_post_status_object( $slug ) ? get_post_status_object( $slug )->label : $slug,
			'flow_rules'    => array( 'is_locked' => false, 'roles_allowed' => array() ),
			'next_statuses' => array(),
		);
	}
}