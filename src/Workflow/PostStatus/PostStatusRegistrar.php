<?php
/**
 * Arquivo responsável pelo registro de status personalizados.
 */

namespace Sults\Writen\Workflow\PostStatus;

use Sults\Writen\Contracts\WPPostStatusProviderInterface;
use Sults\Writen\Workflow\Permissions\RoleDefinitions;
use Sults\Writen\Workflow\PostStatus\StatusConfig;

class PostStatusRegistrar {

	private const RESTRICTED_ROLES_LIST = array(
		RoleDefinitions::REDATOR,
	);

	private WPPostStatusProviderInterface $status_provider;

	public function __construct( WPPostStatusProviderInterface $status_provider ) {
		$this->status_provider = $status_provider;
	}

	public function register(): void {
		$this->register_custom_statuses();
	}

	public function register_custom_statuses(): void {
		$all_configs = StatusConfig::get_all();

		foreach ( $all_configs as $slug => $config ) {
			if ( empty( $config['wp_args'] ) ) {
				continue;
			}

			$args = $config['wp_args'];

			$sults_label   = $config['label'];
			$args['label'] = $sults_label;

			// phpcs:disable
			$args['label_count'] = _n_noop(
				$sults_label . ' <span class="count">(%s)</span>',
				$sults_label . ' <span class="count">(%s)</span>',
				'sultswriten'
			);
			// phpcs:enable

			$this->status_provider->register( $slug, $args );
		}
	}

	public static function get_custom_statuses(): array {
		$output = array();
		foreach ( StatusConfig::get_all() as $slug => $config ) {
			if ( ! empty( $config['wp_args'] ) ) {
				$output[ $slug ] = $config['label'];
			}
		}
		return $output;
	}

	public static function get_restricted_roles(): array {
		return self::RESTRICTED_ROLES_LIST;
	}

	public static function get_restricted_statuses(): array {
		$locked = array();
		foreach ( StatusConfig::get_all() as $slug => $config ) {
			if ( ! empty( $config['flow_rules']['is_locked'] ) ) {
				$locked[] = $slug;
			}
		}
		return $locked;
	}
}