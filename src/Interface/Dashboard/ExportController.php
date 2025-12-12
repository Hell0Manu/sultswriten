<?php
namespace Sults\Writen\Interface\Dashboard;

use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Contracts\PostRepositoryInterface;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\HtmlExtractorInterface;

class ExportController implements HookableInterface {

	private PostRepositoryInterface $post_repo;
	private WPUserProviderInterface $user_provider;
	private HtmlExtractorInterface $extractor;

	public const PAGE_SLUG = 'sults-writen-export';

	public function __construct(
		PostRepositoryInterface $post_repo,
		WPUserProviderInterface $user_provider,
		HtmlExtractorInterface $extractor
	) {
		$this->post_repo     = $post_repo;
		$this->user_provider = $user_provider;
		$this->extractor     = $extractor;
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
		$filters = array(
			's'      => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			'author' => isset( $_GET['author'] ) ? absint( $_GET['author'] ) : '',
			'cat'    => isset( $_GET['cat'] ) ? absint( $_GET['cat'] ) : '',
			'paged'  => isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1,
		);
        // phpcs:enable

		$query = $this->post_repo->get_finished_posts( $filters );

		$cat_dropdown_args   = array(
			'show_option_all' => 'Categorias',
			'name'            => 'cat',
			'selected'        => $filters['cat'],
			'echo'            => 0,
			'hierarchical'    => true,
			'class'           => 'sults-filter-select',
		);
		$categories_dropdown = wp_dropdown_categories( $cat_dropdown_args );

		$author_dropdown = $this->user_provider->get_users_dropdown(
			array(
				'show_option_all' => 'Autores',
				'name'            => 'author',
				'selected'        => $filters['author'],
				'who'             => 'authors',
				'class'           => 'sults-filter-select',
			)
		);

		require __DIR__ . '/views/export-home.php';
	}

	private function render_preview_screen(): void {
		if ( ! isset( $_GET['_wpnonce'] ) ) {
			wp_die( 'Requisição inválida: Nonce ausente.', 'Erro de Segurança', array( 'response' => 403 ) );
		}

		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'sults_preview_' . $post_id ) ) {
			wp_die( 'Link expirado ou inválido.', 'Erro de Segurança', array( 'response' => 403 ) );
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			wp_die( 'Post não encontrado.' );
		}

		$html_raw   = $post->post_content;
		$html_clean = $this->extractor->extract( $post );

		$jsp_content = "\n" .
						"<jsp:include page='...'>\n" . $html_clean . "\n</jsp:include>";

		$back_url = remove_query_arg( array( 'action', 'post_id', '_wpnonce' ) );

		require __DIR__ . '/views/export-preview.php';
	}
}
