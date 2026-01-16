<?php
namespace Sults\Writen\Interface\Dashboard;

use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Contracts\ArchiverInterface;
use Sults\Writen\Workflow\Export\ExportProcessor;
use Sults\Writen\Contracts\ExportNamingServiceInterface;
use Sults\Writen\Contracts\FileSystemInterface;
use Sults\Writen\Contracts\ConfigProviderInterface;

class ExportDownloadHandler implements HookableInterface {

	private ArchiverInterface $archiver;
	private ExportProcessor $processor;
	private ExportNamingServiceInterface $naming_service;
	private FileSystemInterface $filesystem;
	private ConfigProviderInterface $config;

	public function __construct(
		ArchiverInterface $archiver,
		ExportProcessor $processor,
		ExportNamingServiceInterface $naming_service,
		FileSystemInterface $filesystem,
		ConfigProviderInterface $config
	) {
		$this->archiver       = $archiver;
		$this->processor      = $processor;
		$this->naming_service = $naming_service;
		$this->filesystem     = $filesystem;
		$this->config         = $config;
	}

	public function register(): void {
		add_action( 'admin_post_sults_export_download', array( $this, 'handle_request' ) );
	}

	public function handle_request(): void {
		if ( ! isset( $_GET['_wpnonce'] ) || ! isset( $_GET['post_id'] ) ) {
			wp_die( 'Requisição inválida.' );
		}

		$sults_post_id = absint( $_GET['post_id'] );

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'sults_export_' . $sults_post_id ) ) {
			wp_die( 'Link expirado.', 'Erro de Segurança', array( 'response' => 403 ) );
		}

		$sults_post = get_post( $sults_post_id );
        if ( ! $sults_post ) {
            wp_die( 'Post não encontrado.' );
        }

		$raw_slug  = $sults_post->post_name;
		$base_name  = $this->naming_service->generate_zip_filename( $raw_slug );

		$zip_images_prefix = $this->config->get_export_image_prefix();

		try {
			$result    = $this->processor->execute( $sults_post_id, $zip_images_prefix );
			$files_map = $result['files_map'];

			$jsp_folder = isset( $result['jsp_folder_path'] ) ? $result['jsp_folder_path'] : $this->config->get_default_jsp_folder();
			$jsp_folder = rtrim( $jsp_folder, '/' ) . '/';
			
			$jsp_zip_path = $jsp_folder . $base_name . '.jsp';

			$string_map = array(
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

					readfile( $zip_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile

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