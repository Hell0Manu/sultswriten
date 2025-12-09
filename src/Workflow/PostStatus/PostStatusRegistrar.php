<?php
/**
 * Arquivo responsável pelo registro de status personalizados de post.
 *
 * Define a lista de novos status (como Suspenso, Finalizado) e utiliza
 * a infraestrutura do WordPress para registrá-los no sistema.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\PostStatus
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\PostStatus;

use Sults\Writen\Contracts\WPPostStatusProviderInterface;

class PostStatusRegistrar {

	public const CUSTOM_STATUSES = array(
		'suspended'           => 'Suspenso',
		'requires_adjustment' => 'Precisa de Ajustes',
		'review_in_progress'  => 'Revisão em andamento',
		'finished'            => 'Finalizado',
	);

	public const RESTRICTED_ROLES = array(
		'contributor', // Redator.
	);

	public const RESTRICTED_STATUSES = array(
		'review_in_progress',
		'suspended',
		'finished',
	);

	private WPPostStatusProviderInterface $status_provider;

	public function __construct( WPPostStatusProviderInterface $status_provider ) {
		$this->status_provider = $status_provider;
	}

	public function register(): void {
		$this->register_custom_statuses();
	}

	public function register_custom_statuses(): void {
		foreach ( self::CUSTOM_STATUSES as $slug => $label ) {
			$this->status_provider->register(
				$slug,
				array(
					'label'                     => $label,
					'public'                    => false,
					'internal'                  => false,
					'exclude_from_search'       => true,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'protected'                 => true,
					'label_count'               => _n_noop(
						// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingular
						$label . ' <span class="count">(%s)</span>',
						// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralPlural
						$label . ' <span class="count">(%s)</span>',
						'sultswriten'
					),
				)
			);
		}
	}

	public static function get_custom_statuses(): array {
		return self::CUSTOM_STATUSES;
	}

	public static function get_restricted_roles(): array {
		return self::RESTRICTED_ROLES;
	}

	public static function get_restricted_statuses(): array {
		return self::RESTRICTED_STATUSES;
	}
}
