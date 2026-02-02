<?php
/**
 * Gerenciador de Assets da Tela de Exportação.
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
 * Classe ExportAssetsManager.
 *
 * Responsável por carregar scripts e estilos condicionalmente na página "Sults Export".
 *
 * Funcionalidades:
 * 1. Isolamento: Verifica o hook da página para garantir que os assets só carreguem na tela de exportação.
 * 2. Estilização de Tabela: Carrega o CSS moderno para a listagem de posts prontos para exportação.
 * 3. Modo Preview: Se a ação for 'preview', carrega o Editor de Código nativo do WordPress (CodeMirror)
 * para exibir o HTML/JSP gerado com syntax highlighting (realce de sintaxe).
 *
 * @see ExportController
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface\Dashboard
 * @author     Sults
 * @since      0.1.0
 */
class ExportAssetsManager implements HookableInterface {

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
	 * Registra o hook de enfileiramento administrativo.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enfileira scripts e estilos específicos para a exportação.
	 *
	 * @since 0.1.0
	 *
	 * @param string $hook O identificador da página atual.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {

		// Verifica se estamos na página definida pelo ExportController.
		if ( strpos( $hook, ExportController::PAGE_SLUG ) === false ) {
			return;
		}

		$version = $this->asset_resolver->get_version();

		// 1. Estilos Base e Variáveis.
		$this->asset_loader->enqueue_style(
			'sults-writen-variables-css',
			$this->asset_resolver->get_css_url( 'variables.css' ),
			array(),
			$version
		);

		// 2. Estilo da Tabela de Listagem (Sults Modern Table).
		$this->asset_loader->enqueue_style(
			'sults-modern-table-css',
			$this->asset_resolver->get_css_url( 'sults-table.css' ),
			array( 'sults-writen-variables-css' ),
			$version
		);

		// 3. Estilos de Status (Badges coloridos).
		$this->asset_loader->enqueue_style(
			'sults-writen-status-css',
			$this->asset_resolver->get_css_url( 'statusmanager.css' ),
			array( 'sults-writen-variables-css' ),
			$version
		);

		// Injeta as cores dinâmicas dos status.
		$this->asset_loader->add_inline_style( 'sults-writen-status-css', StatusVisuals::get_css_rules() );

		// 4. Lógica Específica para a Tela de Preview.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Leitura visual apenas.
		if ( isset( $_GET['action'] ) && 'preview' === $_GET['action'] ) {

			// Carrega o CodeMirror (Editor de código nativo do WP) configurado para HTML.
			// Isso faz com que o código exportado apareça colorido e formatado.
			wp_enqueue_code_editor( array( 'type' => 'text/html' ) );

			$this->asset_loader->enqueue_style(
				'sults-export-preview-css',
				$this->asset_resolver->get_css_url( 'export-preview.css' ),
				array( 'sults-writen-variables-css' ),
				$version
			);

			$this->asset_loader->enqueue_script(
				'sults-export-preview-js',
				$this->asset_resolver->get_js_url( 'export-preview.js' ),
				array( 'jquery', 'wp-theme-plugin-editor' ), // Dependência do editor de código.
				$version,
				true
			);
		}
	}
}