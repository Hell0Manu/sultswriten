<?php

namespace Sults\Writen\Structure;

use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;
use Sults\Writen\Contracts\HookableInterface;

class StructureManager implements HookableInterface {

    private $user_provider;
    private $asset_loader;
    private $status_provider;

    public function __construct(
        WPUserProviderInterface $user_provider,
        AssetLoaderInterface $asset_loader,
        WPPostStatusProviderInterface $status_provider
    ) {
        $this->user_provider   = $user_provider;
        $this->asset_loader    = $asset_loader;
        $this->status_provider = $status_provider;
    }

    public function register(): void {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_sults_update_structure', [ $this, 'ajax_handle_move' ] );
    }

    public function register_menu(): void {
        add_menu_page(
            __( 'Estrutura de Conteúdo', 'sults-writen' ),
            __( 'Estrutura', 'sults-writen' ),
            'edit_posts',
            'sults-writen-structure',
            [ $this, 'render_page' ],
            'dashicons-networking',
            30
        );
    }

    public function enqueue_assets( $hook ): void {
        if ( strpos( $hook, 'sults-writen-structure' ) === false ) {
            return;
        }

        $plugin_url = plugin_dir_url( dirname( dirname( __DIR__ ) ) . '/sults-writen.php' ); 
        
        // 1. CSS do jsTree (CDN)
        wp_enqueue_style( 'jstree-css', 'https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/themes/default/style.min.css' );
        
        // 2. Nosso CSS (para pequenos ajustes)
        wp_enqueue_style( 'sults-structure-css', $plugin_url . 'src/assets/css/structure.css', [], '1.0.0' );

        // 3. JS do jsTree (CDN)
        wp_enqueue_script( 'jstree-js', 'https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/jstree.min.js', ['jquery'], '3.3.12', true );

        // 4. Nosso JS de controle
        wp_enqueue_script(
            'sults-structure-js',
            $plugin_url . 'src/assets/js/structure.js',
            [ 'jquery', 'jstree-js' ], // Dependência do jstree
            '1.0.0',
            true
        );

        wp_localize_script( 'sults-structure-js', 'sultsStructureParams', [
             'ajax_url' => admin_url( 'admin-ajax.php' ),
             'nonce'    => wp_create_nonce( 'sults_structure_nonce' )
        ]);
        
        // Injeta os dados
        wp_add_inline_script( 
            'sults-structure-js', 
            'const sultsStructureData = ' . json_encode( $this->get_tree_data() ) . ';', // Nota: removi o wrapper ['tree' => ...] para simplificar
            'before'
        );
    }

   /**
     * Processa a mudança de pai e ORDEM via AJAX.
     */
    public function ajax_handle_move() {
        check_ajax_referer( 'sults_structure_nonce', 'security' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Permissão negada.' );
        }

        $post_id   = intval( $_POST['post_id'] );
        $parent_id = intval( $_POST['parent_id'] ); // Vem como 0 se for raiz (tratado no JS)
        $order     = isset($_POST['order']) ? $_POST['order'] : [];

        if ( $post_id === $parent_id ) {
            wp_send_json_error( 'Loop inválido.' );
        }

        // 1. Atualiza o Pai
        $updated = wp_update_post( [
            'ID'          => $post_id,
            'post_parent' => $parent_id
        ] );

        if ( is_wp_error( $updated ) ) {
            wp_send_json_error( $updated->get_error_message() );
        }

        // 2. Atualiza a Ordem (menu_order) dos irmãos
        if ( ! empty( $order ) && is_array( $order ) ) {
            foreach ( $order as $index => $sibling_id ) {
                // O índice do array vira o menu_order (0, 1, 2...)
                wp_update_post( [
                    'ID'         => intval( $sibling_id ),
                    'menu_order' => $index
                ] );
            }
        }

        wp_send_json_success( 'Estrutura e ordem salvas.' );
    }
    public function get_tree_data(): array {
        $statuses = $this->status_provider->get_all_status_slugs(); 
        $args = [
            'post_type'      => 'post',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
            'post_status'    => $statuses
        ];
        $posts = get_posts( $args );

        // Resgate de órfãos
        $valid_ids = [];
        foreach ( $posts as $p ) { $valid_ids[ $p->ID ] = true; }
        foreach ( $posts as $post ) {
            if ( $post->post_parent > 0 && ! isset( $valid_ids[ $post->post_parent ] ) ) {
                $post->post_parent = 0; 
            }
        }
        
        return $this->build_tree( $posts );
    }

    /**
     * Formata para jsTree: id, text, icon, state, children, a_attr
     */
/**
     * Formata para jsTree com Badges HTML
     */
   /**
     * Formata para jsTree com Badges e Ícones
     */
    private function build_tree( array $elements, int $parent_id = 0 ): array {
        $branch = [];
        
        foreach ( $elements as $element ) {
            if ( $element->post_parent == $parent_id ) {
                
                $children = $this->build_tree( $elements, $element->ID );
                $permalink = get_edit_post_link( $element->ID );
                
                // 1. Definição do Ícone
                $icon = 'dashicons dashicons-admin-post'; // Padrão
                if ( $element->post_status === 'publish' ) {
                    $icon = 'dashicons dashicons-yes-alt'; // Vzinho verde (via CSS)
                } elseif ( $element->post_status === 'draft' ) {
                    $icon = 'dashicons dashicons-edit';    // Lápis
                } elseif ( $element->post_status === 'pending' ) {
                    $icon = 'dashicons dashicons-clock';   // Relógio
                }

                // 2. HTML da Badge
                // Nota: O status vai como classe CSS (ex: status-publish) para estilizarmos no CSS
                $status_html = sprintf(
                    ' <span class="sults-badge status-%s">%s</span>',
                    esc_attr( $element->post_status ),
                    esc_html( $element->post_status )
                );

                // 3. Montagem do Item
                $item = [
                    'id'     => (string) $element->ID,
                    // O jstree aceita HTML no text se configurado (padrão aceita)
                    'text'   => esc_html( $element->post_title ) . $status_html, 
                    'icon'   => $icon,
                    'state'  => [ 'opened' => true ], // Inicia expandido
                    'a_attr' => [ 
                        'href'   => $permalink, 
                        'target' => '_blank',
                        'class'  => 'sults-tree-link' // Classe extra se precisar
                    ],
                    'children' => $children
                ];
                
                $branch[] = $item;
            }
        }
        return $branch;
    }
    public function render_page(): void {
        echo '<div class="wrap">
            <h1>Estrutura de Conteúdo</h1>
            <div class="sults-structure-container">
                <input type="text" id="sults-search" placeholder="Buscar post..." style="width:100%; margin-bottom:15px; padding:8px;">
                <div id="sults-structure-app"></div>
            </div>
        </div>';
    }
}