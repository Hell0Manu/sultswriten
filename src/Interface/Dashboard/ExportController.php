<?php
namespace Sults\Writen\Interface\Dashboard;

use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Contracts\PostRepositoryInterface;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\ArchiverInterface;
use Sults\Writen\Workflow\Export\ExportProcessor;
use Sults\Writen\Contracts\ExportNamingServiceInterface;
use Sults\Writen\Contracts\FileSystemInterface;

class ExportController implements HookableInterface {

	private PostRepositoryInterface $post_repo;
	private WPUserProviderInterface $user_provider;
	private ArchiverInterface $archiver;
	private ExportProcessor $processor;
	private ExportNamingServiceInterface $naming_service;
	private FileSystemInterface $filesystem;

	public const PAGE_SLUG = 'sults-writen-export';

	public function __construct(
		PostRepositoryInterface $post_repo,
		WPUserProviderInterface $user_provider,
		ArchiverInterface $archiver,
		ExportProcessor $processor,
		ExportNamingServiceInterface $naming_service,
		FileSystemInterface $filesystem
	) {
		$this->post_repo      = $post_repo;
		$this->user_provider  = $user_provider;
		$this->archiver       = $archiver;
		$this->processor      = $processor;
		$this->naming_service = $naming_service;
		$this->filesystem     = $filesystem;
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
		} elseif ( 'download' === $action ) {
			$this->handle_download();
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

		require __DIR__ . '/views/export-home.php';
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
			$zip_path_prefix = defined( 'SULTSWRITEN_EXPORT_ZIP_PATH' ) ? SULTSWRITEN_EXPORT_ZIP_PATH : 'sults/images/';

			$result = $this->processor->execute( $sults_post_id, $zip_path_prefix );

			$sults_post  = get_post( $sults_post_id );
			$html_raw    = $result['html_raw'];
			$html_clean  = $result['html_clean'];
			$jsp_content = $result['jsp_content'];

			$back_url = remove_query_arg( array( 'action', 'post_id', '_wpnonce' ) );

			require __DIR__ . '/views/export-preview.php';

		} catch ( \Exception $e ) {
			wp_die( esc_html( $e->getMessage() ) );
		}
	}

	private function handle_download(): void {
		if ( ! isset( $_GET['_wpnonce'] ) || ! isset( $_GET['post_id'] ) ) {
			wp_die( 'Requisição inválida.' );
		}

		$sults_post_id = absint( $_GET['post_id'] );

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'sults_export_' . $sults_post_id ) ) {
			wp_die( 'Link expirado.', 'Erro de Segurança', array( 'response' => 403 ) );
		}

		$sults_post = get_post( $sults_post_id );
		$raw_title  = get_the_title( $sults_post );

		$base_name = $this->naming_service->generate_zip_filename( $raw_title );

		$zip_images_prefix = defined( 'SULTSWRITEN_EXPORT_ZIP_PATH' ) ? SULTSWRITEN_EXPORT_ZIP_PATH : 'sults/images/';
		try {
			$result    = $this->processor->execute( $sults_post_id, $zip_images_prefix );
			$files_map = $result['files_map'];

			$jsp_folder = isset( $result['jsp_folder_path'] ) ? $result['jsp_folder_path'] : 'sults/pages/produtos';

			$jsp_folder = rtrim( $jsp_folder, '/' ) . '/';

			$jsp_zip_path = $jsp_folder . $base_name . '.jsp';

			$string_map             = array(
				$jsp_zip_path            => $result['jsp_content'],
				$base_name . '-info.txt' => $result['info_content'],
			);
						$upload_dir = wp_upload_dir();
			$zip_filename_download  = $base_name . '.zip';
			$zip_path               = $upload_dir['basedir'] . '/' . $zip_filename_download;

			if ( $this->archiver->create( $zip_path, $files_map, $string_map ) ) {
				if ( $this->filesystem->exists( $zip_path ) ) {
					if ( ob_get_length() ) {
						ob_end_clean();
					}
					header( 'Content-Description: File Transfer' );
					header( 'Content-Type: application/zip' );
					header( 'Content-Disposition: attachment; filename="' . basename( $zip_path ) . '"' );
					header( 'Expires: 0' );
					header( 'Cache-Control: must-revalidate' );
					header( 'Pragma: public' );
					header( 'Content-Length: ' . filesize( $zip_path ) );

					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
					readfile( $zip_path );

					$this->filesystem->delete( $zip_path );
					exit;
				}
			} else {
				wp_die( 'Erro ao gerar o arquivo ZIP.' );
			}
		} catch ( \Exception $e ) {
			wp_die( esc_html( $e->getMessage() ) );
		}
	}
}
