<?php
namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\ViewRendererInterface;

class SimpleViewRenderer implements ViewRendererInterface {
    private string $base_path;

    /**
     * @param string $base_path Caminho base onde as views estão (ex: .../src/Interface/Dashboard/views/)
     */

    public function __construct( string $base_path ) {
        $this->base_path = rtrim( $base_path, '/' ) . '/';
    }

    public function render( string $view_name, array $data = array() ): void {
        $file_path = $this->base_path . $view_name . '.php';

        if ( ! file_exists( $file_path ) ) {
            trigger_error( 'View não encontrada: ' . esc_html( $file_path ), E_USER_WARNING );
            return;
        }

        if ( ! empty( $data ) ) {
            extract( $data );
        }

        require $file_path;
    }
}