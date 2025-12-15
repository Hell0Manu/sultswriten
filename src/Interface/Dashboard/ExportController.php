<?php
namespace Sults\Writen\Interface\Dashboard;

use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Contracts\PostRepositoryInterface;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\HtmlExtractorInterface;
use Sults\Writen\Contracts\JspBuilderInterface;
use Sults\Writen\Contracts\SeoDataProviderInterface;
use Sults\Writen\Workflow\Export\ExportAssetsManager;
use Sults\Writen\Contracts\ArchiverInterface;

class ExportController implements HookableInterface {

    private ArchiverInterface $archiver; 

    private PostRepositoryInterface $post_repo;
    private WPUserProviderInterface $user_provider;
    private HtmlExtractorInterface $extractor;
    private JspBuilderInterface $jsp_builder;
    private SeoDataProviderInterface $seo_provider;
    private ExportAssetsManager $assets_manager;

    public const PAGE_SLUG = 'sults-writen-export';

    public function __construct(
        PostRepositoryInterface $post_repo,
        WPUserProviderInterface $user_provider,
        HtmlExtractorInterface $extractor,
        JspBuilderInterface $jsp_builder,
        SeoDataProviderInterface $seo_provider,
        ExportAssetsManager $assets_manager,
        ArchiverInterface $archiver
    ) {
        $this->post_repo      = $post_repo;
        $this->user_provider  = $user_provider;
        $this->extractor      = $extractor;
        $this->jsp_builder    = $jsp_builder;
        $this->seo_provider   = $seo_provider;
        $this->assets_manager = $assets_manager;
        $this->archiver       = $archiver;
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

		$zip_path_prefix = defined( 'SULTSWRITEN_EXPORT_ZIP_PATH' ) 
			? SULTSWRITEN_EXPORT_ZIP_PATH 
			: 'sults/images/';
		$assets_payload = $this->assets_manager->process( $html_clean, $zip_path_prefix );
		$final_html_for_jsp = $assets_payload->html_content;
		
		$page_title = get_the_title( $post );
		$seo_data   = $this->seo_provider->get_seo_data( $post_id );

		$jsp_content = $this->jsp_builder->build( $final_html_for_jsp, $page_title, $seo_data );
		$back_url = remove_query_arg( array( 'action', 'post_id', '_wpnonce' ) );

		require __DIR__ . '/views/export-preview.php';
	}

	private function handle_download(): void {
        if ( ! isset( $_GET['_wpnonce'] ) || ! isset( $_GET['post_id'] ) ) {
            wp_die( 'Requisição inválida.' );
        }

        $post_id = absint( $_GET['post_id'] );
        
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'sults_export_' . $post_id ) ) {
            wp_die( 'Link expirado.', 'Erro de Segurança', array( 'response' => 403 ) );
        }

        $post = get_post( $post_id );
        if ( ! $post ) wp_die( 'Post não encontrado.' );

        
        $raw_title = get_the_title( $post );
        
        $base_name = sanitize_title( $raw_title );

        $char_limit = 50;
        if ( strlen( $base_name ) > $char_limit ) {
            $base_name = substr( $base_name, 0, $char_limit );
            $base_name = rtrim( $base_name, '-' ); 
        }

        if ( empty( $base_name ) ) {
            $base_name = 'exportacao-sults';
        }

        $zip_images_prefix = $base_name . '/images/';
        $jsp_filename_inside_zip = $base_name . '/' . $base_name . '.jsp';


        $html_clean = $this->extractor->extract( $post );
        
        $assets_payload  = $this->assets_manager->process( $html_clean, $zip_images_prefix );

        $seo_data = $this->seo_provider->get_seo_data( $post_id );
        
        $jsp_content = $this->jsp_builder->build( $assets_payload->html_content, get_the_title( $post ), $seo_data );

        $files_map  = $assets_payload->files_to_zip;
        
        $string_map = array( $jsp_filename_inside_zip => $jsp_content );

        $upload_dir = wp_upload_dir();

        $zip_filename_download = $base_name . '.zip';
        $zip_path   = $upload_dir['basedir'] . '/' . $zip_filename_download;

        if ( $this->archiver->create( $zip_path, $files_map, $string_map ) ) {
            
            if ( file_exists( $zip_path ) ) {

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
                readfile( $zip_path );
                
                unlink( $zip_path );
                exit;
            }
        } else {
            wp_die( 'Erro ao gerar o arquivo ZIP. Verifique as permissões da pasta uploads.' );
        }
    }
}
