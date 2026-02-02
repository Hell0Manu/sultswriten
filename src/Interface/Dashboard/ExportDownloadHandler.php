<?php
/**
 * Manipulador de Download de Exportação.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface\Dashboard
 * @since      0.1.0
 */

namespace Sults\Writen\Interface\Dashboard;

use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Contracts\ArchiverInterface;
use Sults\Writen\Workflow\Export\ExportProcessor;
use Sults\Writen\Contracts\ExportNamingServiceInterface;
use Sults\Writen\Contracts\FileSystemInterface;
use Sults\Writen\Contracts\ConfigProviderInterface;

/**
 * Classe ExportDownloadHandler.
 *
 * Responsável por gerenciar a requisição HTTP que gera e entrega o arquivo ZIP.
 *
 * Fluxo de Execução:
 * 1. Recebe a requisição via `admin_post_`.
 * 2. Valida segurança (Nonce e Permissões).
 * 3. Invoca o `ExportProcessor` para gerar o conteúdo (HTML/JSP) e mapear imagens.
 * 4. Utiliza o `ArchiverInterface` para criar um ZIP físico contendo:
 * - O arquivo .jsp (gerado em memória).
 * - O arquivo -info.txt (gerado em memória).
 * - As imagens (copiadas do disco do servidor).
 * 5. Envia o arquivo para o navegador (Stream) e o exclui do servidor imediatamente após.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface\Dashboard
 * @author     Sults
 * @since      0.1.0
 */
class ExportDownloadHandler implements HookableInterface {

	/** @var ArchiverInterface Serviço de compactação (ZIP). */
	private ArchiverInterface $archiver;

	/** @var ExportProcessor Lógica central de transformação de conteúdo. */
	private ExportProcessor $processor;

	/** @var ExportNamingServiceInterface Serviço de nomenclatura de arquivos. */
	private ExportNamingServiceInterface $naming_service;

	/** @var FileSystemInterface Abstração do sistema de arquivos. */
	private FileSystemInterface $filesystem;

	/** @var ConfigProviderInterface Configurações gerais. */
	private ConfigProviderInterface $config;

	/**
	 * Construtor.
	 */
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

	/**
	 * Registra o hook para lidar com requisições POST/GET administrativas.
	 *
	 * O hook `admin_post_` permite processar requisições sem renderizar a interface do admin.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_post_sults_export_download', array( $this, 'handle_request' ) );
	}

	/**
	 * Processa a requisição de download.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function handle_request(): void {
		// 1. Validação de Segurança.
		if ( ! isset( $_GET['_wpnonce'] ) || ! isset( $_GET['post_id'] ) ) {
			wp_die( 'Requisição inválida.' );
		}

		$sults_post_id = absint( $_GET['post_id'] );

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'sults_export_' . $sults_post_id ) ) {
			wp_die( 'Link expirado.', 'Erro de Segurança', array( 'response' => 403 ) );
		}

		// 2. Validação do Post.
		$sults_post = get_post( $sults_post_id );
		if ( ! $sults_post ) {
			wp_die( 'Post não encontrado.' );
		}

		// 3. Preparação de Nomes e Caminhos.
		$raw_slug          = $sults_post->post_name;
		$zip_base_name     = $this->naming_service->generate_zip_filename( $raw_slug );
		$base_name         = $this->naming_service->generate_zip_filename( $raw_slug );
		$zip_images_prefix = $this->config->get_export_image_prefix();

		try {
			// 4. Execução do Processamento (Heavy Lifting).
			// Gera o HTML limpo, JSP e lista de imagens.
			$result    = $this->processor->execute( $sults_post_id, $zip_images_prefix );
			$files_map = $result['files_map']; // Imagens físicas.

			// Define onde o JSP vai ficar dentro do ZIP (estrutura de pastas Java).
			$jsp_folder = isset( $result['jsp_folder_path'] ) ? $result['jsp_folder_path'] : $this->config->get_default_jsp_folder();
			$jsp_folder = rtrim( $jsp_folder, '/' ) . '/';
			
			// Define o nome do arquivo JSP interno.
			if ( ! empty( $result['suggested_filename'] ) ) {
				 $internal_filename = $result['suggested_filename'];
			} else {
				 $internal_filename = $zip_base_name;
			}

			$jsp_zip_path = $jsp_folder . $internal_filename . '.jsp';

			// Mapa de arquivos virtuais (conteúdo em memória -> arquivo no ZIP).
			$string_map = array(
				$jsp_zip_path                    => $result['jsp_content'],
				$internal_filename . '-info.txt' => $result['info_content'],
			);

			// 5. Criação do ZIP Temporário.
			$upload_dir            = wp_upload_dir();
			$zip_filename_download = $zip_base_name . '.zip';
			$zip_path              = $upload_dir['basedir'] . '/' . $zip_filename_download;

			if ( $this->archiver->create( $zip_path, $files_map, $string_map ) ) {
				
				// 6. Entrega (Streaming).
				if ( $this->filesystem->exists( $zip_path ) ) {
					// Limpa buffers de saída para evitar corromper o binário.
					if ( ob_get_length() ) {
						ob_end_clean();
					}
					
					// Headers para forçar download.
					header( 'Content-Description: File Transfer' );
					header( 'Content-Type: application/zip' );
					header( 'Content-Disposition: attachment; filename="' . basename( $zip_path ) . '"' );
					header( 'Expires: 0' );
					header( 'Cache-Control: must-revalidate' );
					header( 'Pragma: public' );
					header( 'Content-Length: ' . filesize( $zip_path ) );

					readfile( $zip_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile

					// Remove o arquivo temporário após o envio para economizar espaço.
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