<?php
/**
 * Gerenciador de Assets do Workspace.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface\Dashboard
 * @since      0.1.0
 */

namespace Sults\Writen\Interface\Dashboard;

use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Infrastructure\AssetPathResolver;
use Sults\Writen\Workflow\PostStatus\StatusVisuals;

/**
 * Classe WorkspaceAssetsManager.
 *
 * Responsável pelo carregamento condicional de scripts e estilos na página do Workspace.
 *
 * Performance e Isolamento:
 * Verifica o hook da página atual para garantir que o CSS/JS do Workspace só seja carregado
 * quando o usuário estiver efetivamente nessa tela (`sults-writen-workspace`).
 * Isso previne conflitos de estilo com o restante do painel administrativo do WordPress.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface\Dashboard
 * @author     Sults
 * @since      0.1.0
 */
class WorkspaceAssetsManager implements HookableInterface {

	/** @var AssetLoaderInterface Enfileirador de scripts. */
	private AssetLoaderInterface $asset_loader;

	/** @var AssetPathResolver Resolvedor de caminhos de arquivo. */
	private AssetPathResolver $asset_resolver;

	/**
	 * Construtor.
	 *
	 * @param AssetLoaderInterface $asset_loader   Serviço de carregamento.
	 * @param AssetPathResolver    $asset_resolver Serviço de resolução de caminhos.
	 */
	public function __construct(
		AssetLoaderInterface $asset_loader,
		AssetPathResolver $asset_resolver
	) {
		$this->asset_loader   = $asset_loader;
		$this->asset_resolver = $asset_resolver;
	}

	/**
	 * Registra o hook de enfileiramento de scripts administrativos.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enfileira os estilos específicos do Workspace.
	 *
	 * Lógica:
	 * 1. Verifica se estamos na página do Workspace. Se não, aborta.
	 * 2. Carrega variáveis CSS globais.
	 * 3. Carrega o layout do Workspace (`workspace.css`).
	 * 4. Carrega estilos de gerenciamento de status e injeta CSS dinâmico (cores dos badges).
	 *
	 * @since 0.1.0
	 *
	 * @param string $hook O identificador da página atual do admin.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		// Verifica se o hook contém o slug da nossa página.
		if ( strpos( $hook, 'sults-writen-workspace' ) === false ) {
			return;
		}

		$version = $this->asset_resolver->get_version();

		// 1. Variáveis de Design (Cores, Fontes).
		$this->asset_loader->enqueue_style(
			'sults-writen-variables-css',
			$this->asset_resolver->get_css_url( 'variables.css' ),
			array(),
			$version
		);

		// 2. Layout Principal do Workspace (Grid, Cards, Tabelas).
		$this->asset_loader->enqueue_style(
			'sults-writen-workspace-css',
			$this->asset_resolver->get_css_url( 'workspace.css' ),
			array( 'sults-writen-variables-css' ),
			$version
		);

		// 3. Estilos de Status e Badges.
		$this->asset_loader->enqueue_style(
			'sults-writen-status-css',
			$this->asset_resolver->get_css_url( 'statusmanager.css' ),
			array( 'sults-writen-workspace-css' ),
			$version
		);

		// Injeção de CSS Dinâmico:
		// Gera as regras de cor (background/text) para cada status definido na configuração,
		// garantindo que novos status tenham cores sem precisar editar arquivos .css.
		$this->asset_loader->add_inline_style( 'sults-writen-status-css', StatusVisuals::get_css_rules() );
	}
}