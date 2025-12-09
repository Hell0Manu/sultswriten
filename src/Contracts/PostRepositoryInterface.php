<?php

namespace Sults\Writen\Contracts;

interface PostRepositoryInterface {
	public function get_posts_for_workspace( int $author_id ): \WP_Query;
}
