<?php
	/**
	 * Verifica se a requisição atual é do tipo POST.
	 *
	 * @return bool
	 */
namespace Sults\Writen\Infrastructure;

class RequestBlocker {
	public function is_post_method(): bool {
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) ) {
			return false;
		}

		$method = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) );
		return 'POST' === $method;
	}
}
