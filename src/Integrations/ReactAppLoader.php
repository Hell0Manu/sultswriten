<?php

namespace Sults\Writen\Integrations;

use Sults\Writen\Contracts\HookableInterface;

class ReactAppLoader implements HookableInterface {

    public function register(): void {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_app' ) );
        add_filter( 'script_loader_tag', array( $this, 'add_type_module' ), 10, 3 );
    }

    public function add_type_module( $tag, $handle, $src ) {
        if ( 'sults-react-app' !== $handle ) {
            return $tag;
        }
        
        return '<script type="module" src="' . esc_url( $src ) . '" id="sults-react-app-js"></script>';
    }

    public function enqueue_app( $hook ): void {
        $screen = get_current_screen();

        if ( ! $screen || strpos( $screen->id, 'sults-writen' ) === false ) {
            return;
        }

        $plugin_root_path = plugin_dir_path( dirname( __DIR__, 2 ) . '/sults-writen.php' );
        $plugin_root_url  = plugin_dir_url( dirname( __DIR__, 2 ) . '/sults-writen.php' );

        $react_assets_path = $plugin_root_path . 'assets/react-app/';
        $react_assets_url  = $plugin_root_url . 'assets/react-app/';

        $manifest_path = $react_assets_path . '.vite/manifest.json';
        
        if ( ! file_exists( $manifest_path ) ) {
            $manifest_path = $react_assets_path . 'manifest.json';
            if ( ! file_exists( $manifest_path ) ) {
                return;
            }
        }

        $manifest = json_decode( file_get_contents( $manifest_path ), true );
        $js_file = $manifest['index.html']['file'];
        
        wp_enqueue_script( 
            'sults-react-app', 
            $react_assets_url . $js_file, 
            array(), 
            null, 
            true 
        );

        if ( ! empty( $manifest['index.html']['css'] ) ) {
            foreach ( $manifest['index.html']['css'] as $css_file ) {
                wp_enqueue_style( 'sults-react-css', $react_assets_url . $css_file, array(), null );
            }
        }

        wp_localize_script( 'sults-react-app', 'sultsSettings', array(
            'rootUrl'  => get_rest_url(),
            'adminUrl' => admin_url(),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
            'user'     => array(
                'id'   => get_current_user_id(),
                'name' => wp_get_current_user()->display_name
            )
        ));
    }
}