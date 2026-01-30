<?php

namespace Sults\Writen\Integrations;

use Sults\Writen\Contracts\HookableInterface;

class ReactAppLoader implements HookableInterface {

    public function register(): void {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_app' ) );
        add_filter( 'script_loader_tag', array( $this, 'add_type_module' ), 10, 3 );
    }

    public function add_type_module( $tag, $handle, $src ) {
        if ( ! in_array( $handle, array( 'sults-react-app', 'sults-vite-client' ), true ) ) {
            return $tag;
        }

        return '<script type="module" src="' . esc_url( $src ) . '" id="' . esc_attr( $handle ) . '-js"></script>';
    }

    public function enqueue_app( $hook ): void {
        $screen = get_current_screen();

        if ( ! $screen || strpos( $screen->id, 'sults-writen' ) === false ) {
            return;
        }

        $handle = 'sults-react-app';
        $ver    = '1.0.0';

        $is_dev = defined( 'SULTS_WRITEN_DEV_MODE' ) && SULTS_WRITEN_DEV_MODE;

        if ( $is_dev ) {
            wp_enqueue_script( 'sults-vite-client', 'http://localhost:5173/@vite/client', array(), null, false );
            wp_enqueue_script( $handle, 'http://localhost:5173/src/main.tsx', array( 'sults-vite-client' ), null, false );

        } else {
            $plugin_dir_path = plugin_dir_path( dirname( __DIR__, 2 ) . '/sults-writen.php' );
            $plugin_dir_url  = plugin_dir_url( dirname( __DIR__, 2 ) . '/sults-writen.php' );
            
            $react_assets_path = $plugin_dir_path . 'assets/react-app/';
            $react_assets_url  = $plugin_dir_url . 'assets/react-app/';
            
            $manifest_path = $react_assets_path . '.vite/manifest.json';
            if ( ! file_exists( $manifest_path ) ) {
                $manifest_path = $react_assets_path . 'manifest.json';
            }

            if ( file_exists( $manifest_path ) ) {
                $manifest = json_decode( file_get_contents( $manifest_path ), true );
                
                $entry_key = isset($manifest['src/main.tsx']) ? 'src/main.tsx' : 'index.html';

                if ( isset( $manifest[$entry_key] ) ) {
                    $js_file = $manifest[$entry_key]['file'];
                    
                    wp_enqueue_script( 
                        $handle, 
                        $react_assets_url . $js_file, 
                        array(), 
                        $ver, 
                        true 
                    );

                    if ( ! empty( $manifest[$entry_key]['css'] ) ) {
                        foreach ( $manifest[$entry_key]['css'] as $css_file ) {
                            wp_enqueue_style( 'sults-react-css', $react_assets_url . $css_file, array(), $ver );
                        }
                    }
                }
            }
        }

        wp_localize_script( $handle, 'sultsSettings', array(
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