<?php
namespace Sults\Writen\Interface\Editor;

use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Infrastructure\AssetPathResolver;
use Sults\Writen\Workflow\PostStatus\StatusConfig;
use Sults\Writen\Workflow\PostStatus\StatusVisuals;

class GutenbergManager implements HookableInterface {

    private AssetLoaderInterface $asset_loader;
    private AssetPathResolver $asset_resolver;

    public function __construct(
        AssetLoaderInterface $asset_loader,
        AssetPathResolver $asset_resolver
    ) {
        $this->asset_loader   = $asset_loader;
        $this->asset_resolver = $asset_resolver;
    }

    public function register(): void {
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_scripts' ) );
        add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
    }

    public function enqueue_editor_scripts(): void {
        $version = $this->asset_resolver->get_version();

        $this->asset_loader->enqueue_script(
            'sults-writen-gutenberg-restrictions',
            $this->asset_resolver->get_js_url( 'gutenberg-restrictions.js' ),
            array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post', 'wp-hooks', 'lodash' ),
            $version,
            true
        );

        $this->asset_loader->enqueue_script(
            'sults-writen-gutenberg-workflow',
            $this->asset_resolver->get_js_url( 'gutenberg-workflow.js' ),
            array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'jquery' ),
            $version,
            true
        );
        $status_map = array();
        
        if ( class_exists( StatusConfig::class ) ) {
            $all_statuses = StatusConfig::get_all();

            if ( isset( $all_statuses['draft'] ) ) {
                $all_statuses['auto-draft'] = $all_statuses['draft'];
            }

            foreach ( $all_statuses as $slug => $config ) {
                $next_slug = isset($config['next_statuses'][0]) ? $config['next_statuses'][0] : null;
                $label = $config['label'] ?? $slug;

                $save_text = "Salvar {$label}";
                if ( $slug === 'auto-draft' || $slug === 'draft' ) {
                    $save_text = "Salvar Rascunho";
                }


                $flow_text = "Atualizar";
                $flow_target = null; 

                if ( $slug === 'publish' ) {
                    $flow_text = "Atualizar Post";
                } elseif ( $next_slug && isset( $all_statuses[ $next_slug ] ) ) {
                    $next_label = $all_statuses[ $next_slug ]['label'];
                    
                    if ( $next_slug === 'publish' ) {
                        $flow_text = "Publicar Agora";
                    } else {
                        $flow_text = "Enviar para {$next_label}";
                    }
                    $flow_target = $next_slug; 
                } else {
                    $flow_text = "Salvar {$label}";
                }

                $status_map[ $slug ] = array(
                    'secondary' => array(
                        'text' => $save_text,
                        'target_status' => null 
                    ),
                    'primary' => array(
                        'text' => $flow_text,
                        'target_status' => $flow_target 
                    )
                );
            }
        }

        $this->asset_loader->localize_script(
            'sults-writen-gutenberg-workflow',
            'sultsWorkflowParams',
            array(
                'ajax_url'     => admin_url( 'admin-ajax.php' ),
                'nonce'        => wp_create_nonce( 'sults_structure_nonce' ),
                'statusMap'    => $status_map,
                'defaultLabel' => 'Salvar'
            )
        );
    }

    public function enqueue_block_styles(): void {
        $version = $this->asset_resolver->get_version();

        $this->asset_loader->enqueue_style(
            'sults-writen-variables',
            $this->asset_resolver->get_css_url( 'variables.css' ),
            array(),
            $version
        );

        $this->asset_loader->enqueue_style(
            'sults-writen-gutenberg-styles',
            $this->asset_resolver->get_css_url( 'gutenberg-styles.css' ),
            array( 'sults-writen-variables' ),
            $version
        );

        if ( class_exists( StatusVisuals::class ) ) {
            $status_css = StatusVisuals::get_css_rules();
            if ( ! empty( $status_css ) ) {
                wp_add_inline_style( 'sults-writen-gutenberg-styles', $status_css );
            }
        }
    }

    public function enqueue_frontend_scripts(): void {
        $version = $this->asset_resolver->get_version();
        $this->asset_loader->enqueue_script(
            'sults-writen-legacy-tips',
            $this->asset_resolver->get_js_url( 'legacy-tips-frontend.js' ),
            array( 'jquery' ),
            $version,
            true
        );
         $this->asset_loader->localize_script(
            'sults-writen-legacy-tips',
            'sultsWritenSettings',
            array(
                'tipsIconUrl' => $this->asset_resolver->get_image_url( 'modulo-checklist.webp' ),
            )
        );
    }
}