<?php
namespace Sults\Writen\Workflow\Media;

use Sults\Writen\Contracts\ImageProcessorInterface;
use Sults\Writen\Contracts\HookableInterface;

class MediaUploadManager implements HookableInterface {

	private ImageProcessorInterface $image_processor;

	public function __construct( ImageProcessorInterface $image_processor ) {
		$this->image_processor = $image_processor;
	}

	public function register(): void {
		add_filter( 'wp_handle_upload', array( $this, 'handle_upload_conversion' ) );
	}

	public function handle_upload_conversion( array $upload ): array {
		return $this->image_processor->process( $upload );
	}
}
