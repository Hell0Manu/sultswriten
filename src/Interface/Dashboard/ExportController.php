<?php
namespace Sults\Writen\Interface\Dashboard;

use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Contracts\PostRepositoryInterface;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Workflow\Export\ExportProcessor;
use Sults\Writen\Contracts\ConfigProviderInterface;
use Sults\Writen\Contracts\ViewRendererInterface;

class ExportController implements HookableInterface {

	private PostRepositoryInterface $post_repo;
	private WPUserProviderInterface $user_provider;
	private ExportProcessor $processor; 
	private ConfigProviderInterface $config;
	private ViewRendererInterface $view;

	public const PAGE_SLUG = 'sults-writen-export';

	public function __construct(
		PostRepositoryInterface $post_repo,
		WPUserProviderInterface $user_provider,
		ExportProcessor $processor,
		ConfigProviderInterface $config,
		ViewRendererInterface $view
	) {
		$this->post_repo      = $post_repo;
		$this->user_provider  = $user_provider;
		$this->processor      = $processor;
		$this->config         = $config;
		$this->view           = $view;
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
	}

	public function add_menu_page(): void {
		add_menu_page(
			'Sults Export',
			'Sults Export',
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render' ),
			'dashicons-download',
			3
		);
	}

	public function render(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list';

		if ( 'preview' === $action ) {
			$this->render_preview_screen();
		} else {
			$this->render_list_screen();
		}
	}

	private function render_list_screen(): void {
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

		$query = $this->post_repo->get_finished_posts( $paged, $search, $cat_id, $author_id );

		$sults_cat_dropdown_args   = array(
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


	private function render_preview_screen(): void {
		if ( ! isset( $_GET['_wpnonce'] ) ) {
			wp_die( 'Requisição inválida: Nonce ausente.', 'Erro de Segurança', array( 'response' => 403 ) );
		}

		$sults_post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'sults_preview_' . $sults_post_id ) ) {
			wp_die( 'Link expirado ou inválido.', 'Erro de Segurança', array( 'response' => 403 ) );
		}

		try {
			$zip_path_prefix = $this->config->get_export_image_prefix();
			$result = $this->processor->execute( $sults_post_id, $zip_path_prefix );

			$sults_post  = get_post( $sults_post_id );
			$html_raw    = $result['html_raw'];
			$html_clean  = $result['html_clean'];
			$jsp_content = $result['jsp_content'];

			$back_url = remove_query_arg( array( 'action', 'post_id', '_wpnonce' ) );

		$this->view->render( 'export-preview', array(
            'sults_post'  => $sults_post,
            'html_raw'    => $result['html_raw'],
            'html_clean'  => $result['html_clean'],
            'jsp_content' => $result['jsp_content'],
            'back_url'    => remove_query_arg( array( 'action', 'post_id', '_wpnonce' ) ),
        ) );

		} catch ( \Exception $e ) {
			wp_die( esc_html( $e->getMessage() ) );
		}
	}
}
