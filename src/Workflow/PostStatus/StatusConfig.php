<?php
namespace Sults\Writen\Workflow\PostStatus;

use Sults\Writen\Workflow\Permissions\RoleDefinitions;

class StatusConfig {
	public const SUSPENDED           = 'suspended';
	public const REQUIRES_ADJUSTMENT = 'requires_adjustment';
	public const REVIEW_IN_PROGRESS  = 'review_in_progress';
	public const FINISHED            = 'finished';

	public const DRAFT   = 'draft';
	public const PENDING = 'pending';
	public const PUBLISH = 'publish';

	public static function get_all(): array {
		return array(
			self::SUSPENDED           => array(
				'label'      => 'Suspenso',
				'css_class'  => 'sults-status-suspended',
				'style'      => array(
					'bg'   => 'var(--color-red-500)',
					'text' => 'var(--color-red-100)',
				),
				'wp_args'    => array(
					'public'                    => false,
					'internal'                  => false,
					'exclude_from_search'       => true,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'protected'                 => true,
				),
				'flow_rules' => array(
					'is_locked'     => true,
					'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE ),
				),
			),
			self::REQUIRES_ADJUSTMENT => array(
				'label'      => 'Precisa de Ajustes',
				'css_class'  => 'sults-status-requires_adjustment',
				'style'      => array(
					'bg'   => 'var(--color-orange-500)',
					'text' => 'var(--color-orange-100)',
				),
				'wp_args'    => array(
					'public'                    => false,
					'exclude_from_search'       => true,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'protected'                 => true,
				),
				'flow_rules' => array(
					'is_locked'     => false,
					'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE, RoleDefinitions::REDATOR ),
				),
			),
			self::REVIEW_IN_PROGRESS  => array(
				'label'      => 'Revisão em andamento',
				'css_class'  => 'sults-status-revisao_em_andamento',
				'style'      => array(
					'bg'   => 'var(--color-blue-500)',
					'text' => 'var(--color-blue-100)',
				),
				'wp_args'    => array(
					'public'                    => false,
					'exclude_from_search'       => true,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'protected'                 => true,
				),
				'flow_rules' => array(
					'is_locked'     => true,
					'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE ),
				),
			),
			self::FINISHED            => array(
				'label'      => 'Finalizado',
				'css_class'  => 'sults-status-finished',
				'style'      => array(
					'bg'   => 'var(--color-green-500)',
					'text' => 'var(--color-green-100)',
				),
				'wp_args'    => array(
					'public'                    => false,
					'exclude_from_search'       => true,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'protected'                 => true,
				),
				'flow_rules' => array(
					'is_locked'     => true,
					'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE ),
				),
			),
		);
	}

	/**
	 * Retorna a configuração de um status, com fallback para nativos.
	 */
	public static function get_config( string $slug ): array {
		$all = self::get_all();

		if ( isset( $all[ $slug ] ) ) {
			return $all[ $slug ];
		}

		$defaults = array(
			'publish' => array(
				'bg'   => 'var(--color-verdigris-500)',
				'text' => 'var(--color-verdigris-100)',
			),
			'draft'   => array(
				'bg'   => 'var(--color-neutral-500)',
				'text' => 'var(--color-neutral-100)',
			),
			'pending' => array(
				'bg'   => 'var(--color-yellow-500)',
				'text' => 'var(--color-yellow-100)',
			),
			'future'  => array(
				'bg'   => 'var(--color-blue-500)',
				'text' => 'var(--color-blue-100)',
			),
			'private' => array(
				'bg'   => 'var(--color-neutral-800)',
				'text' => 'var(--color-neutral-100)',
			),
		);

		$style = isset( $defaults[ $slug ] ) ? $defaults[ $slug ] : array(
			'bg'   => '#e2e4e7',
			'text' => '#50575e',
		);

		return array(
			'label'      => get_post_status_object( $slug ) ? get_post_status_object( $slug )->label : $slug,
			'css_class'  => 'sults-status-' . $slug,
			'style'      => $style,
			'flow_rules' => array(
				'is_locked'     => false,
				'roles_allowed' => array(),
			),
		);
	}

	/**
	 * Gera as regras CSS para todos os status (customizados e nativos).
	 * Centraliza a lógica visual para evitar duplicação nos Assets Managers.
	 */
	public static function get_css_rules(): string {
		$css     = '';
		$configs = self::get_all();

		$native_slugs = array( 'publish', 'draft', 'pending', 'private', 'future' );
		foreach ( $native_slugs as $slug ) {
			$configs[ $slug ] = self::get_config( $slug );
		}

		foreach ( $configs as $config ) {
			if ( empty( $config['css_class'] ) || empty( $config['style'] ) ) {
				continue;
			}

			$selector = '.' . $config['css_class'];
			$bg       = $config['style']['bg'];
			$text     = $config['style']['text'];

			$css .= "{$selector} { background: {$bg}; color: {$text}; } ";
		}

		return $css;
	}
}
