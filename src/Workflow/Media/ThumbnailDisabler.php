<?php
namespace Sults\Writen\Workflow\Media;

class ThumbnailDisabler {

	public function register(): void {
		add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array' );
		add_filter( 'big_image_size_threshold', '__return_false' );
	}
}
