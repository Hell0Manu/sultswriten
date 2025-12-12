<?php
namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\HookableInterface;

class FeatureDisabler implements HookableInterface {

	public function register(): void {
		add_action( 'init', array( $this, 'disable_features' ) );
		add_filter( 'comments_open', '__return_false', 20 );
		add_filter( 'pings_open', '__return_false', 20 );
	}

	public function disable_features(): void {
		unregister_taxonomy_for_object_type( 'post_tag', 'post' );

		remove_post_type_support( 'post', 'comments' );
		remove_post_type_support( 'post', 'trackbacks' );
	}
}
