<?php
/**
 * Gerenciador de Assets (CSS/JS) do fluxo de status.
 *
 * Responsável por enfileirar os arquivos de estilo e script nas telas
 * administrativas corretas e injetar dados de configuração (como a lista
 * de status e permissões) para o JavaScript.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\PostStatus
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\PostStatus;

use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Contracts\WPUserProviderInterface;

class AdminAssetsManager {

	private string $plugin_url;
	private string $plugin_version;
	private AssetLoaderInterface $asset_loader;
	private WPUserProviderInterface $user_provider;
	private array $allowed_roles = array( 'administrator', 'editor' );

	public function __construct(
		string $plugin_url,
		string $plugin_version,
		AssetLoaderInterface $asset_loader,
		WPUserProviderInterface $user_provider
	) {
		$this->plugin_url     = $plugin_url;
		$this->plugin_version = $plugin_version;
		$this->asset_loader   = $asset_loader;
		$this->user_provider  = $user_provider;
	}

	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'edit.php' ), true ) ) {
			return;
		}

		$user_roles = $this->user_provider->get_current_user_roles();

		$vars_path = 'src/assets/css/variables.css';
		$this->asset_loader->enqueue_style(
			'sultswriten-variables-css',
			$this->plugin_url . $vars_path,
			array(),
			$this->plugin_version
		);

		$css_path = 'src/assets/css/statusManager.css';
		$this->asset_loader->enqueue_style(
			'sultswriten-status-css',
			$this->plugin_url . $css_path,
			array( 'sultswriten-variables-css' ),
			$this->plugin_version
		);

		$js_path = 'src/assets/js/statusManager.js';
		$this->asset_loader->enqueue_script(
			'sultswriten-statuses',
			$this->plugin_url . $js_path,
			array( 'jquery' ),
			$this->plugin_version,
			true
		);

		$this->asset_loader->localize_script(
			'sultswriten-statuses',
			'SultsWritenStatuses',
			array(
				'statuses'      => PostStatusRegistrar::get_custom_statuses(),
				'current_roles' => $user_roles,
				'allowed_roles' => apply_filters( 'sultswriten_allowed_status_roles', $this->allowed_roles ),
			)
		);
	}
}