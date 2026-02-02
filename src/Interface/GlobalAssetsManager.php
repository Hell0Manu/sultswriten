<?php
/**
 * Gerenciador Global de Assets (CSS/JS).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface
 * @since      0.1.0
 */

namespace Sults\Writen\Interface;

use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Infrastructure\AssetPathResolver;

/**
 * Classe GlobalAssetsManager.
 *
 * Responsável por centralizar o registro de scripts e estilos do plugin.
 *
 * Estratégia de Carregamento:
 * 1. Registro Centralizado (`register_admin_assets`): Define *onde* os arquivos estão e suas dependências,
 * mas não os carrega imediatamente. Isso cria uma biblioteca de assets reutilizáveis.
 * 2. Carregamento Seletivo: Outras classes (Controllers) enfileiram apenas o que precisam
 * usando os handles registrados aqui.
 * 3. Estilos do Editor (`enqueue_block_styles`): Carrega variáveis CSS e estilos globais
 * dentro do editor de blocos (Gutenberg) para garantir paridade visual com o frontend.
 *
 * @see AssetPathResolver
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface
 * @author     Sults
 * @since      0.1.0
 */
class GlobalAssetsManager implements HookableInterface {

	/**
	 * Carregador de assets (wrapper para wp_enqueue_*).
	 * @var AssetLoaderInterface
	 */
	private AssetLoaderInterface $asset_loader;

	/**
	 * Resolvedor de caminhos e URLs (evita hardcoding).
	 * @var AssetPathResolver
	 */
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
	 * Registra os hooks de assets.
	 *
	 * - `admin_enqueue_scripts` (prioridade 1): Executa cedo para registrar os handles antes que outros plugins tentem usá-los.
	 * - `enqueue_block_assets`: Injeta CSS no editor de blocos.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_assets' ), 1 );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_styles' ) );
	}

	/**
	 * Registra (sem enfileirar) os scripts e estilos administrativos.
	 *
	 * Define a árvore de dependências:
	 * Variables -> Status CSS -> Structure CSS
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_admin_assets(): void {
		$version = $this->asset_resolver->get_version();

		// 1. Variáveis CSS (Cores, Espaçamentos) - Base de tudo.
		wp_register_style(
			'sults-writen-variables',
			$this->asset_resolver->get_css_url( 'variables.css' ),
			array(),
			$version
		);

		// 2. Status Manager (CSS & JS) - Componente de troca de status.
		wp_register_style(
			'sults-writen-status-css',
			$this->asset_resolver->get_css_url( 'statusmanager.css' ),
			array( 'sults-writen-variables' ),
			$version
		);

		wp_register_script(
			'sults-writen-status-js',
			$this->asset_resolver->get_js_url( 'statusManager.js' ),
			array( 'jquery' ),
			$version,
			true
		);

		// 3. Structure (CSS & JS) - Componente de Workspace.
		wp_register_style(
			'sults-writen-structure-css',
			$this->asset_resolver->get_css_url( 'structure.css' ),
			array( 'sults-writen-variables', 'sults-writen-status-css' ),
			$version
		);

		wp_register_script(
			'sults-writen-structure-js',
			$this->asset_resolver->get_js_url( 'structure.js' ),
			array( 'jquery', 'jquery-ui-sortable' ), // Depende de Sortable para Drag & Drop.
			$version,
			true
		);
	}

	/**
	 * Enfileira estilos globais no contexto do Editor de Blocos e Admin.
	 *
	 * Garante que as variáveis CSS e o estilo global estejam presentes em toda parte.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function enqueue_block_styles(): void {
		$version = $this->asset_resolver->get_version();

		$this->asset_loader->enqueue_style(
			'sults-writen-variables',
			$this->asset_resolver->get_css_url( 'variables.css' ),
			array(),
			$version
		);

		$this->asset_loader->enqueue_style(
			'sults-writen-global',
			$this->asset_resolver->get_css_url( 'global.css' ),
			array( 'sults-writen-variables' ),
			$version
		);
	}
}