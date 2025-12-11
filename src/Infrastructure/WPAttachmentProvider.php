<?php
namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\AttachmentProviderInterface;

class WPAttachmentProvider implements AttachmentProviderInterface {

	public function get_attachment_id_by_url( string $url ): int {
		$id = attachment_url_to_postid( $url );
		if ( $id ) {
			return $id;
		}
		return url_to_postid( $url );
	}

	public function get_image_src( int $attachment_id, string $size = 'full' ): ?array {
		$image = wp_get_attachment_image_src( $attachment_id, $size );
		return $image ? $image : null;
	}

	public function get_attachment_url( int $attachment_id ): ?string {
		$url = wp_get_attachment_url( $attachment_id );
		return $url ? $url : null;
	}
}
