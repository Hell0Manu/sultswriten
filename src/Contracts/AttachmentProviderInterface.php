<?php
namespace Sults\Writen\Contracts;

interface AttachmentProviderInterface {
	public function get_attachment_id_by_url( string $url ): int;
	public function get_image_src( int $attachment_id, string $size = 'full' ): ?array;
	public function get_attachment_url( int $attachment_id ): ?string;
}
