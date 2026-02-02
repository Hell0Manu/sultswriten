<?php
/**
 * Processador de Imagens com GD Library.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure\Media
 * @since      0.1.0
 */

namespace Sults\Writen\Infrastructure\Media;

use Sults\Writen\Contracts\ImageProcessorInterface;

/**
 * Classe GDWebPProcessor.
 *
 * Responsável por otimizar imagens enviadas para o servidor.
 * Realiza duas operações principais automaticamente:
 * 1. Redimensionamento: Garante que nenhuma imagem exceda a largura máxima (padrão 850px),
 * otimizando o armazenamento e o tempo de carregamento no editor.
 * 2. Conversão: Transforma arquivos JPG/PNG em WebP, um formato mais leve e moderno.
 *
 * A classe substitui o arquivo original pelo otimizado, economizando espaço em disco.
 *
 * @see ImageProcessorInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure\Media
 * @author     Sults
 * @since      0.1.0
 */
class GDWebPProcessor implements ImageProcessorInterface {

	/**
	 * Largura máxima permitida em pixels.
	 * Imagens mais largas que isso serão redimensionadas proporcionalmente.
	 *
	 * @var int
	 */
	private const MAX_WIDTH = 850;

	/**
	 * Qualidade da compressão WebP (0 a 100).
	 *
	 * @var int
	 */
	private const QUALITY = 85;

	/**
	 * Processa o upload da imagem.
	 *
	 * Intercepta o array de upload do WordPress, realiza a conversão/redimensionamento
	 * e atualiza os caminhos para apontar para o novo arquivo WebP gerado.
	 *
	 * @since 0.1.0
	 *
	 * @param array $upload Array contendo informações do arquivo enviado ('file', 'url', 'type').
	 * @return array O array $upload modificado, agora apontando para o arquivo .webp.
	 */
	public function process( array $upload ): array {
		$file_path = $upload['file'];

		// Verifica a extensão.
		$ext = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'jpg', 'jpeg', 'png' ), true ) ) {
			return $upload;
		}

		// Obtém dimensões originais.
		$image_info = getimagesize( $file_path );
		if ( ! $image_info ) {
			return $upload;
		}

		list( $width, $height ) = $image_info;

		// Calcula novas dimensões mantendo a proporção.
		if ( $width > self::MAX_WIDTH ) {
			$ratio      = self::MAX_WIDTH / $width;
			$new_width  = self::MAX_WIDTH;
			$new_height = intval( $height * $ratio );
		} else {
			$new_width  = $width;
			$new_height = $height;
		}

		// Carrega a imagem na memória usando GD.
		$image = null;
		if ( 'png' === $ext ) {
			$image = imagecreatefrompng( $file_path );
			// Preserva transparência do PNG.
			imagepalettetotruecolor( $image );
			imagealphablending( $image, true );
			imagesavealpha( $image, true );
		} else {
			$image = imagecreatefromjpeg( $file_path );
		}

		if ( ! $image ) {
			return $upload;
		}

		// Redimensiona.
		$resized = imagescale( $image, $new_width, $new_height );
		
		// Define o novo caminho com extensão .webp.
		$webp_path = preg_replace( '/\.(jpg|jpeg|png)$/i', '.webp', $file_path );

		// Salva como WebP.
		imagewebp( $resized, $webp_path, self::QUALITY );

		// Libera memória.
		imagedestroy( $image );
		imagedestroy( $resized );

		// Se a conversão funcionou, atualiza o array de retorno e deleta o original.
		if ( file_exists( $webp_path ) ) {
			$upload['file'] = $webp_path;
			$upload['url']  = preg_replace( '/\.(jpg|jpeg|png)$/i', '.webp', $upload['url'] );
			$upload['type'] = 'image/webp';

			// Remove o arquivo original (JPG/PNG) para não duplicar arquivos.
			if ( file_exists( $file_path ) && $file_path !== $webp_path ) {
				wp_delete_file( $file_path );
			}
		}

		return $upload;
	}
}