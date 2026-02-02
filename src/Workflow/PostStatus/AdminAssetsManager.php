<?php
/**
 * Gerenciador de Assets Administrativos do Workflow.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\PostStatus
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\PostStatus;

use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Infrastructure\AssetPathResolver;
use Sults\Writen\Workflow\PostStatus\StatusVisuals;

/**
 * Classe AdminAssetsManager.
 *
 * Responsável por carregar scripts e estilos necessários para a interface de workflow
 * no painel administrativo (telas de edição e listagem de posts).
 *
 * Principais responsabilidades:
 * 1. Enfileirar CSS base e Variáveis.
 * 2. Injetar CSS dinâmico para cores de status (gerado por `StatusVisuals`).
 * 3. Localizar dados para o JavaScript (`SultsWritenStatuses`), passando apenas
 * as informações permitidas para o usuário atual (segurança/UX).
 *
 * @see StatusVisuals
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\PostStatus
 * @author     Sults
 * @since      0.1.0
 */
class AdminAssetsManager {

	/**
	 * Carregador de assets (wrapper).
	 * @var AssetLoaderInterface
	 */
	private AssetLoaderInterface $asset_loader;

	/**
	 * Provedor de dados do usuário.
	 * @var WPUserProviderInterface
	 */
	private WPUserProviderInterface $user_provider;

	/**
	 * Resolvedor de caminhos de arquivos.
	 * @var AssetPathResolver
	 */
	private AssetPathResolver $asset_resolver;

	/**
	 * Construtor.
	 *
	 * @param AssetLoaderInterface    $asset_loader   Serviço de carregamento de scripts/styles.
	 * @param WPUserProviderInterface $user_provider  Serviço para obter roles do usuário.
	 * @param AssetPathResolver       $asset_resolver Serviço para obter URLs de assets.
	 */
	public function __construct(
		AssetLoaderInterface $asset_loader,
		WPUserProviderInterface $user_provider,
		AssetPathResolver $asset_resolver
	) {
		$this->asset_loader   = $asset_loader;
		$this->user_provider  = $user_provider;
		$this->asset_resolver = $asset_resolver;
	}

	/**
	 * Registra o hook de enfileiramento de scripts do admin.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enfileira os scripts e estilos nas páginas relevantes.
	 *
	 * Executa apenas nas telas de edição de post, novo post e listagem de posts.
	 *
	 * @since 0.1.0
	 * @param string $hook O sufixo da página atual do admin.
	 * @return void
	 */
	public function enqueue_scripts( string $hook ): void {
		// Otimização: Carrega apenas nas telas de postagem.
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'edit.php' ), true ) ) {
			return;
		}

		$user_roles = $this->user_provider->get_current_user_roles();

		// Enfileira os estilos principais (assumindo que foram registrados previamente no plugin core).
		wp_enqueue_style( 'sults-writen-variables' );
		wp_enqueue_style( 'sults-writen-status-css' );

		// Gera e injeta o CSS dinâmico das cores dos status.
		$custom_css = StatusVisuals::get_css_rules();
		$this->asset_loader->add_inline_style( 'sults-writen-status-css', $custom_css );

		// Enfileira o JS de controle de status.
		wp_enqueue_script( 'sults-writen-status-js' );

		// Prepara os dados para o JavaScript (Localization).
		// Filtra os status para enviar apenas aqueles que o usuário tem permissão de ver/usar em regras.
		$all_configs       = StatusConfig::get_all();
		$filtered_statuses = array();

		foreach ( $all_configs as $slug => $config ) {
			$allowed        = isset( $config['flow_rules']['roles_allowed'] ) ? $config['flow_rules']['roles_allowed'] : array();
			$has_permission = false;

			// Verifica se o usuário tem alguma das roles permitidas para este status.
			foreach ( $user_roles as $role ) {
				if ( in_array( $role, $allowed, true ) ) {
					$has_permission = true;
					break;
				}
			}

			// Se tiver permissão, adiciona à lista enviada ao JS.
			if ( $has_permission ) {
				$filtered_statuses[ $slug ] = $config['label'];
			}
		}

		// Passa o objeto 'SultsWritenStatuses' para o contexto global do JS.
		$this->asset_loader->localize_script(
			'sults-writen-status-js',
			'SultsWritenStatuses',
			array(
				'statuses'      => $filtered_statuses,
				'current_roles' => $user_roles,
				// Fallback de segurança.
				'allowed_roles' => $user_roles,
			)
		);
	}
}