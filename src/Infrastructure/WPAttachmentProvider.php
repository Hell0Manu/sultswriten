<?php
/**
 * Implementação do provedor de anexos via WordPress.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */

namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\AttachmentProviderInterface;

/**
 * Classe WPAttachmentProvider.
 *
 * Wrapper concreto para funções de mídia do WordPress.
 * Facilita a recuperação de IDs de imagens a partir de URLs e vice-versa,
 * abstraindo funções como `attachment_url_to_postid` e `wp_get_attachment_image_src`.
 *
 * @see AttachmentProviderInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @author     Sults
 * @since      0.1.0
 */
class WPAttachmentProvider implements AttachmentProviderInterface {

	/**
	 * {@inheritDoc}
	 *
	 * Tenta primeiro encontrar pelo URL exato do anexo.
	 * Se falhar, tenta usar `url_to_postid` como fallback.
	 */
	public function get_attachment_id_by_url( string $url ): int {
		$id = attachment_url_to_postid( $url );
		if ( $id ) {
			return $id;
		}
		return url_to_postid( $url );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_image_src( int $attachment_id, string $size = 'full' ): ?array {
		$image = wp_get_attachment_image_src( $attachment_id, $size );
		// Retorna null explicitamente se falhar (false), para bater com a tipagem ?array.
		return $image ? $image : null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_attachment_url( int $attachment_id ): ?string {
		$url = wp_get_attachment_url( $attachment_id );
		return $url ? $url : null;
	}
}