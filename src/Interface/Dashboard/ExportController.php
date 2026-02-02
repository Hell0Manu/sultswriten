<?php
/**
 * Controlador da Tela de Exportação (Dashboard).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface\Dashboard
 * @since      0.1.0
 */

namespace Sults\Writen\Interface\Dashboard;

use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Contracts\PostRepositoryInterface;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Workflow\Export\ExportProcessor;
use Sults\Writen\Contracts\ConfigProviderInterface;
use Sults\Writen\Contracts\ViewRendererInterface;

/**
 * Classe ExportController.
 *
 * Responsável por gerenciar a página de administração "Sults Export".
 *
 * Implementa um fluxo simples de duas telas:
 * 1. Lista (`render_list_screen`): Tabela com filtros para selecionar posts finalizados.
 * 2. Preview (`render_preview_screen`): Visualização do resultado da conversão (HTML/JSP) antes do download.
 *
 * @see ExportProcessor
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface\Dashboard
 * @author     Sults
 * @since      0.1.0
 */
class ExportController implements HookableInterface {

	/**
	 * Slug da página no menu do WordPress.
	 */
	public const PAGE_SLUG = 'sults-writen-export';

	/**
	 * Repositório de posts.
	 * @var PostRepositoryInterface
	 */
	private PostRepositoryInterface $post_repo;

	/**
	 * Provedor de usuários.
	 * @var WPUserProviderInterface
	 */
	private WPUserProviderInterface $user_provider;

	/**
	 * Processador de exportação (Lógica de Negócio).
	 * @var ExportProcessor
	 */
	private ExportProcessor $processor; 

	/**
	 * Provedor de configurações.
	 * @var ConfigProviderInterface
	 */
	private ConfigProviderInterface $config;

	/**
	 * Renderizador de Views (Templates).
	 * @var ViewRendererInterface
	 */
	private ViewRendererInterface $view;

	/**
	 * Construtor.
	 *
	 * @param PostRepositoryInterface $post_repo     Acesso a dados dos posts.
	 * @param WPUserProviderInterface $user_provider Acesso a dados de autores.
	 * @param ExportProcessor         $processor     Serviço de conversão HTML->JSP.
	 * @param ConfigProviderInterface $config        Configurações gerais (ex: prefixos de imagem).
	 * @param ViewRendererInterface   $view          Sistema de templates.
	 */
	public function __construct(
		PostRepositoryInterface $post_repo,
		WPUserProviderInterface $user_provider,
		ExportProcessor $processor,
		ConfigProviderInterface $config,
		ViewRendererInterface $view
	) {
		$this->post_repo     = $post_repo;
		$this->user_provider = $user_provider;
		$this->processor     = $processor;
		$this->config        = $config;
		$this->view          = $view;
	}

	/**
	 * Registra o menu administrativo.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
	}

	/**
	 * Adiciona a página ao menu lateral do WordPress.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function add_menu_page(): void {
		add_menu_page(
			'Sults Export',      // Título da página
			'Sults Export',      // Título do menu
			'manage_options',    // Capacidade necessária
			self::PAGE_SLUG,     // Slug da URL
			array( $this, 'render' ),
			'dashicons-download', // Ícone
			3                    // Posição
		);
	}

	/**
	 * Método principal de renderização (Router).
	 *
	 * Decide qual tela exibir com base no parâmetro GET 'action'.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function render(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list';

		if ( 'preview' === $action ) {
			$this->render_preview_screen();
		} else {
			$this->render_list_screen();
		}
	}

	/**
	 * Renderiza a tela de listagem (Home da Exportação).
	 *
	 * Processa os filtros de busca (texto, autor, categoria) e paginação,
	 * busca os dados no repositório e carrega a view 'export-home'.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private function render_list_screen(): void {
		// Leitura de parâmetros de filtro (sem nonce pois é uma visualização segura).
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$search    = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : null;
		$author_id = isset( $_GET['author'] ) && '' !== $_GET['author'] ? absint( $_GET['author'] ) : null;
		$cat_id    = isset( $_GET['cat'] ) && '' !== $_GET['cat'] && -1 !== (int) $_GET['cat'] ? absint( $_GET['cat'] ) : null;
		$paged     = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		// phpcs:enable

		$filters = array(
			's'      => $search,
			'author' => $author_id,
			'cat'    => $cat_id,
			'paged'  => $paged
		);

		// Busca apenas posts "finalizados" (prontos para exportação).
		$query = $this->post_repo->get_finished_posts( $paged, $search, $cat_id, $author_id );

		// Prepara dropdowns de filtro.
		$sults_cat_dropdown_args = array(
			'show_option_all' => 'Categorias',
			'name'            => 'cat',
			'selected'        => $filters['cat'],
			'echo'            => 0,
			'hierarchical'    => true,
			'class'           => 'sults-filter-select',
		);
		$sults_categories_dropdown = wp_dropdown_categories( $sults_cat_dropdown_args );

		$sults_author_dropdown = $this->user_provider->get_users_dropdown(
			array(
				'show_option_all' => 'Autores',
				'name'            => 'author',
				'selected'        => $filters['author'],
				'capability'      => 'edit_posts',
				'class'           => 'sults-filter-select',
			)
		);

		$this->view->render( 'export-home', array(
			'query'                     => $query,
			'filters'                   => $filters,
			'sults_categories_dropdown' => $sults_categories_dropdown,
			'sults_author_dropdown'     => $sults_author_dropdown,
		) );
	}

	/**
	 * Renderiza a tela de pré-visualização da exportação.
	 *
	 * Executa o processador de exportação para gerar o HTML limpo e o JSP,
	 * exibindo o resultado para conferência antes do download final.
	 *
	 * Validações de segurança (Nonce) são aplicadas aqui pois é uma ação sensível.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private function render_preview_screen(): void {
		if ( ! isset( $_GET['_wpnonce'] ) ) {
			wp_die( 'Requisição inválida: Nonce ausente.', 'Erro de Segurança', array( 'response' => 403 ) );
		}

		$sults_post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

		// Verifica o nonce específico para este post.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'sults_preview_' . $sults_post_id ) ) {
			wp_die( 'Link expirado ou inválido.', 'Erro de Segurança', array( 'response' => 403 ) );
		}

		try {
			// Executa a lógica de transformação.
			$zip_path_prefix = $this->config->get_export_image_prefix();
			$result = $this->processor->execute( $sults_post_id, $zip_path_prefix );

			$sults_post  = get_post( $sults_post_id );
			$back_url    = remove_query_arg( array( 'action', 'post_id', '_wpnonce' ) );

			$this->view->render( 'export-preview', array(
				'sults_post'  => $sults_post,
				'html_raw'    => $result['html_raw'],
				'html_clean'  => $result['html_clean'],
				'jsp_content' => $result['jsp_content'],
				'back_url'    => $back_url,
			) );

		} catch ( \Exception $e ) {
			wp_die( esc_html( $e->getMessage() ) );
		}
	}
}
