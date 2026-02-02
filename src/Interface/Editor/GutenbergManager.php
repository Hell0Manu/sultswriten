<?php
/**
 * Gerenciador do Editor de Blocos (Gutenberg).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface\Editor
 * @since      0.1.0
 */

namespace Sults\Writen\Interface\Editor;

use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Infrastructure\AssetPathResolver;
use Sults\Writen\Workflow\PostStatus\StatusConfig;
use Sults\Writen\Workflow\PostStatus\StatusVisuals;

/**
 * Classe GutenbergManager.
 *
 * Responsável por personalizar a experiência de edição dentro do WordPress.
 *
 * Funcionalidades:
 * 1. Restrições de Blocos: Enfileira scripts que removem blocos não suportados pelo sistema legado.
 * 2. Workflow no Editor: Substitui a lógica padrão de "Publicar" por botões de fluxo de trabalho
 * (ex: "Enviar para Revisão", "Devolver para Ajuste") via JavaScript injetado.
 * 3. Estilização: Garante que o visual do editor (WYSIWYG) corresponda ao CSS do frontend legado.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface\Editor
 * @author     Sults
 * @since      0.1.0
 */
class GutenbergManager implements HookableInterface {

	/** @var AssetLoaderInterface Enfileirador de scripts/estilos. */
	private AssetLoaderInterface $asset_loader;

	/** @var AssetPathResolver Resolvedor de URLs de assets. */
	private AssetPathResolver $asset_resolver;

	/**
	 * Construtor.
	 */
	public function __construct(
		AssetLoaderInterface $asset_loader,
		AssetPathResolver $asset_resolver
	) {
		$this->asset_loader   = $asset_loader;
		$this->asset_resolver = $asset_resolver;
	}

	/**
	 * Registra os hooks de customização do editor.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		// Scripts funcionais do editor (JS).
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_scripts' ) );
		
		// Estilos visuais do editor (CSS).
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_styles' ) );
		
		// Scripts para o frontend (Preview).
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
	}

	/**
	 * Enfileira scripts JS que modificam o comportamento do Gutenberg.
	 *
	 * Inclui:
	 * - `gutenberg-restrictions.js`: Remove blocos não permitidos.
	 * - `gutenberg-workflow.js`: Adiciona a barra de status e botões de ação personalizados.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function enqueue_editor_scripts(): void {
		$version = $this->asset_resolver->get_version();

		// 1. Script de Restrições (Allowlist de Blocos).
		$this->asset_loader->enqueue_script(
			'sults-writen-gutenberg-restrictions',
			$this->asset_resolver->get_js_url( 'gutenberg-restrictions.js' ),
			array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post', 'wp-hooks', 'lodash' ),
			$version,
			true
		);

		// 2. Script de Workflow (Botões de Ação).
		$this->asset_loader->enqueue_script(
			'sults-writen-gutenberg-workflow',
			$this->asset_resolver->get_js_url( 'gutenberg-workflow.js' ),
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'jquery' ),
			$version,
			true
		);

		// Prepara o mapa de status para o JS saber quais botões desenhar.
		$status_map = array();
		
		if ( class_exists( StatusConfig::class ) ) {
			$all_statuses = StatusConfig::get_all();

			// Normaliza rascunhos.
			if ( isset( $all_statuses['draft'] ) ) {
				$all_statuses['auto-draft'] = $all_statuses['draft'];
			}

			foreach ( $all_statuses as $slug => $config ) {
				$next_slug = isset($config['next_statuses'][0]) ? $config['next_statuses'][0] : null;
				$label     = $config['label'] ?? $slug;

				// Botão Secundário: Salvar no estado atual.
				$save_text = "Salvar {$label}";
				if ( $slug === 'auto-draft' || $slug === 'draft' ) {
					$save_text = "Salvar Rascunho";
				}

				// Botão Primário: Avançar Workflow.
				$flow_text   = "Atualizar";
				$flow_target = null; 

				if ( $slug === 'publish' ) {
					$flow_text = "Atualizar Post";
				} elseif ( $next_slug && isset( $all_statuses[ $next_slug ] ) ) {
					$next_label = $all_statuses[ $next_slug ]['label'];
					
					if ( $next_slug === 'publish' ) {
						$flow_text = "Publicar Agora";
					} else {
						$flow_text = "Enviar para {$next_label}";
					}
					$flow_target = $next_slug; 
				} else {
					$flow_text = "Salvar {$label}";
				}

				$status_map[ $slug ] = array(
					'secondary' => array(
						'text'          => $save_text,
						'target_status' => null // Null significa "manter status atual".
					),
					'primary' => array(
						'text'          => $flow_text,
						'target_status' => $flow_target 
					)
				);
			}
		}

		// Envia os dados para o Frontend (JS).
		$this->asset_loader->localize_script(
			'sults-writen-gutenberg-workflow',
			'sultsWorkflowParams',
			array(
				'ajax_url'     => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'sults_structure_nonce' ),
				'statusMap'    => $status_map,
				'defaultLabel' => 'Salvar'
			)
		);
	}

	/**
	 * Enfileira estilos CSS para o editor.
	 *
	 * Garante que o que o redator vê no editor seja muito próximo do resultado final exportado.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function enqueue_block_styles(): void {
		$version = $this->asset_resolver->get_version();

		// Variáveis globais.
		$this->asset_loader->enqueue_style(
			'sults-writen-variables',
			$this->asset_resolver->get_css_url( 'variables.css' ),
			array(),
			$version
		);

		// Estilos específicos do editor (ex: largura do container).
		$this->asset_loader->enqueue_style(
			'sults-writen-gutenberg-styles',
			$this->asset_resolver->get_css_url( 'gutenberg-styles.css' ),
			array( 'sults-writen-variables' ),
			$version
		);

		// Injeta CSS dinâmico das cores de status (badges).
		if ( class_exists( StatusVisuals::class ) ) {
			$status_css = StatusVisuals::get_css_rules();
			if ( ! empty( $status_css ) ) {
				wp_add_inline_style( 'sults-writen-gutenberg-styles', $status_css );
			}
		}
	}

	/**
	 * Scripts para o Frontend (Preview) do WordPress.
	 *
	 * Necessário para funcionalidades interativas legadas, como as "Dicas Sults" que usam JS.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function enqueue_frontend_scripts(): void {
		$version = $this->asset_resolver->get_version();
		
		$this->asset_loader->enqueue_script(
			'sults-writen-legacy-tips',
			$this->asset_resolver->get_js_url( 'legacy-tips-frontend.js' ),
			array( 'jquery' ),
			$version,
			true
		);

		// Configurações (ícone da dica).
		$this->asset_loader->localize_script(
			'sults-writen-legacy-tips',
			'sultsWritenSettings',
			array(
				'tipsIconUrl' => $this->asset_resolver->get_image_url( 'dica-sults.webp' ),
			)
		);
	}
}
