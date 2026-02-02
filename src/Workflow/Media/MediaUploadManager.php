<?php
/**
 * Gerenciador de Upload de Mídia.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Media
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Media;

use Sults\Writen\Contracts\ImageProcessorInterface;
use Sults\Writen\Contracts\HookableInterface;

/**
 * Classe MediaUploadManager.
 *
 * Responsável por interceptar o fluxo de upload nativo do WordPress e aplicar
 * transformações automáticas nos arquivos de imagem.
 *
 * Utiliza o hook `wp_handle_upload` para processar o arquivo físico logo após
 * ele ser movido para o diretório de uploads, mas antes de gerar os metadados
 * da biblioteca de mídia. Isso permite, por exemplo, forçar a conversão de
 * PNG para JPG ou WebP automaticamente na entrada.
 *
 * @see ImageProcessorInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Media
 * @author     Sults
 * @since      0.1.0
 */
class MediaUploadManager implements HookableInterface {

	/**
	 * Processador de imagens (Lógica de conversão/otimização).
	 * @var ImageProcessorInterface
	 */
	private ImageProcessorInterface $image_processor;

	/**
	 * Construtor.
	 *
	 * @param ImageProcessorInterface $image_processor Serviço de manipulação de imagem.
	 */
	public function __construct( ImageProcessorInterface $image_processor ) {
		$this->image_processor = $image_processor;
	}

	/**
	 * Registra o filtro de tratamento de upload.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		add_filter( 'wp_handle_upload', array( $this, 'handle_upload_conversion' ) );
	}

	/**
	 * Callback executado durante o upload do arquivo.
	 *
	 * Delega o arquivo para o `ImageProcessor`. Se o processador alterar o arquivo
	 * (ex: converter ext), o array retornado conterá os novos caminhos, e o WordPress
	 * passará a usar o arquivo novo.
	 *
	 * @since 0.1.0
	 *
	 * @param array $upload Dados do arquivo enviado (file, url, type).
	 * @return array Dados do arquivo processado (possivelmente modificados).
	 */
	public function handle_upload_conversion( array $upload ): array {
		return $this->image_processor->process( $upload );
	}
}