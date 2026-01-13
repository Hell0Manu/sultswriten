<?php
/**
 * Converte e redimensiona imagem original para WebP.
 *
 * Verifica se o arquivo é JPG/PNG. Se for:
 * 1. Redimensiona para largura máxima de 910px (mantendo proporção).
 * 2. Converte para WebP com qualidade 85.
 * 3. Substitui o arquivo original pelo WebP.
 * 4. Atualiza os dados do array de upload para refletir o novo arquivo.
 *
 * @param array $upload Array contendo informações do arquivo enviado ('file', 'url', 'type').
 * @return array O array $upload modificado apontando para o novo arquivo .webp.
 */

namespace Sults\Writen\Infrastructure\Media;

use Sults\Writen\Contracts\ImageProcessorInterface;

class GDWebPProcessor implements ImageProcessorInterface {

	private const MAX_WIDTH = 850;
	private const QUALITY   = 85;

	public function process( array $upload ): array {
		$file_path = $upload['file'];

		$ext = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'jpg', 'jpeg', 'png' ), true ) ) {
			return $upload;
		}

		$image_info = getimagesize( $file_path );
		if ( ! $image_info ) {
			return $upload;
		}

		list( $width, $height ) = $image_info;

		if ( $width > self::MAX_WIDTH ) {
			$ratio      = self::MAX_WIDTH / $width;
			$new_width  = self::MAX_WIDTH;
			$new_height = intval( $height * $ratio );
		} else {
			$new_width  = $width;
			$new_height = $height;
		}

		$image = null;
		if ( 'png' === $ext ) {
			$image = imagecreatefrompng( $file_path );
			imagepalettetotruecolor( $image );
			imagealphablending( $image, true );
			imagesavealpha( $image, true );
		} else {
			$image = imagecreatefromjpeg( $file_path );
		}

		if ( ! $image ) {
			return $upload;
		}

		$resized   = imagescale( $image, $new_width, $new_height );
		$webp_path = preg_replace( '/\.(jpg|jpeg|png)$/i', '.webp', $file_path );

		imagewebp( $resized, $webp_path, self::QUALITY );

		imagedestroy( $image );
		imagedestroy( $resized );

		if ( file_exists( $webp_path ) ) {
			$upload['file'] = $webp_path;
			$upload['url']  = preg_replace( '/\.(jpg|jpeg|png)$/i', '.webp', $upload['url'] );
			$upload['type'] = 'image/webp';

			if ( file_exists( $file_path ) && $file_path !== $webp_path ) {
				wp_delete_file( $file_path );
			}
		}

		return $upload;
	}
}
