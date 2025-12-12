<?php
namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\RequestProviderInterface;

class RequestBlocker implements RequestProviderInterface {
    
    public function is_post_method(): bool {
        if ( ! isset( $_SERVER['REQUEST_METHOD'] ) ) {
            return false;
        }

        $method = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) );
        return 'POST' === $method;
    }
}