<?php

namespace Sults\Writen\Structure;

use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;
use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Workflow\PostStatus\StatusConfig;
use Sults\Writen\Interface\CategoryColorManager;
use Sults\Writen\Workflow\WorkflowPolicy;
use Sults\Writen\Workflow\Permissions\RoleDefinitions;

class StructureManager implements HookableInterface {

    private $user_provider;
    private $asset_loader;
    private $status_provider;
    private $color_manager;
    private $policy;

    public function __construct(
        WPUserProviderInterface $user_provider,
        AssetLoaderInterface $asset_loader,
        WPPostStatusProviderInterface $status_provider,
        CategoryColorManager $color_manager,
        WorkflowPolicy $policy
    ) {
        $this->user_provider   = $user_provider;
        $this->asset_loader    = $asset_loader;
        $this->status_provider = $status_provider;
        $this->color_manager   = $color_manager;
        $this->policy          = $policy;
    }

    private function can_manage_structure(): bool {
        $user = wp_get_current_user();
        $allowed_roles = [ 'administrator', 'editor_chefe' ]; 
        return (bool) array_intersect( $allowed_roles, (array) $user->roles );
    }

    public function register(): void {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_sults_update_structure', [ $this, 'ajax_handle_move' ] );
    }

    public function register_menu(): void {
        add_menu_page(
            __( 'Estrutura', 'sults-writen' ),
            __( 'Estrutura', 'sults-writen' ),
            'edit_posts',
            'sults-writen-structure',
            [ $this, 'render_page' ],
            'dashicons-networking',
            30
        );
    }

    public function enqueue_assets( $hook ): void {
        if ( strpos( $hook, 'sults-writen-structure' ) === false ) return;

        $plugin_url = plugin_dir_url( dirname( dirname( __DIR__ ) ) . '/sults-writen.php' ); 
        
        wp_enqueue_style( 'sults-variables-css', $plugin_url . 'src/assets/css/variables.css', [], '1.0.0' );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_style( 'sults-status-manager-css', $plugin_url . 'src/assets/css/statusmanager.css', [], '1.0.0' );
        
        wp_enqueue_style( 'sults-structure-css', $plugin_url . 'src/assets/css/structure.css', [], '2.8.0' );
        wp_add_inline_style( 'sults-structure-css', "
            .sults-card.disabled { opacity: 0.6; background: #fcfcfc; }
            .sults-card.disabled .sults-card-title { pointer-events: none; color: #a0a5aa; text-decoration: none; cursor: default; }
            .sults-card.disabled:hover { border-color: #e2e4e7; box-shadow: none; }
            .sults-action-icon.disabled { pointer-events: none; cursor: default; color: #d63638; }
            
            /* CSS IMPORTANTE: Esconde a lista vazia para não poluir o visual */
            ul.sults-sortable-nested:empty {
                min-height: 10px; /* Altura mínima para 'pegar' o drop */
                padding: 0;
                margin: 0;
                border: none; /* Remove a linha pontilhada quando vazia */
            }
            /* Quando estiver arrastando algo para cima dela, o sortable adiciona um placeholder, 
               então ela deixa de ser :empty e a borda aparece magicamente! */
        " );
        
        if ( class_exists( StatusConfig::class ) ) {
            $status_css = StatusConfig::get_css_rules();
            wp_add_inline_style( 'sults-structure-css', $status_css );
        }

        wp_enqueue_script( 'sults-structure-js', $plugin_url . 'src/assets/js/structure.js', ['jquery', 'jquery-ui-sortable'], '2.2.0', true );

        wp_localize_script( 'sults-structure-js', 'sultsStructureParams', [
             'ajax_url'   => admin_url( 'admin-ajax.php' ),
             'nonce'      => wp_create_nonce( 'sults_structure_nonce' ),
             'can_manage' => $this->can_manage_structure() 
        ]);
    }

    public function ajax_handle_move() {
        check_ajax_referer( 'sults_structure_nonce', 'security' );
        if ( ! $this->can_manage_structure() ) wp_send_json_error( 'Sem permissão.' );

        $post_id   = intval( $_POST['post_id'] );
        $parent_id = intval( $_POST['parent_id'] );
        $order     = isset($_POST['order']) ? $_POST['order'] : [];

        if ( $post_id === $parent_id ) wp_send_json_error( 'Loop.' );

        wp_update_post( [ 'ID' => $post_id, 'post_parent' => $parent_id ] );

        if ( ! empty( $order ) && is_array( $order ) ) {
            foreach ( $order as $index => $sibling_id ) {
                wp_update_post( [
                    'ID'         => intval( $sibling_id ),
                    'menu_order' => $index
                ] );
            }
        }
        wp_send_json_success();
    }

    public function render_page(): void {
        $tree_html = $this->get_tree_html();
        echo '<div class="wrap"><h1>Estrutura de Conteúdo</h1><div class="sults-structure-wrapper">' . $tree_html . '</div></div>';
    }

    private function get_tree_html(): string {
        $statuses = $this->status_provider->get_all_status_slugs(); 
        $args = [ 
            'post_type'      => 'post', 
            'posts_per_page' => -1, 
            'orderby'        => 'menu_order title', 
            'order'          => 'ASC', 
            'post_status'    => $statuses 
        ];
        $posts = get_posts( $args );

        $current_user_roles = $this->user_provider->get_current_user_roles();

        $posts_by_parent = [];
        $all_posts_map = [];
        foreach ($posts as $p) {
            $all_posts_map[$p->ID] = $p;
            $posts_by_parent[$p->post_parent][] = $p;
        }

        foreach ($posts as $post) {
            if ($post->post_parent > 0 && !isset($all_posts_map[$post->post_parent])) {
                $post->post_parent = 0;
                $posts_by_parent[0][] = $post;
            }
        }

        $root_posts = $posts_by_parent[0] ?? [];
        $category_buckets = []; 
        $uncategorized_posts = [];

        foreach ($root_posts as $post) {
            $cats = get_the_category($post->ID);
            if (empty($cats)) {
                $uncategorized_posts[] = $post;
            } else {
                $primary_cat = $cats[0]; 
                $category_buckets[$primary_cat->term_id][] = $post;
            }
        }

        $active_categories_data = [];
        $all_categories = get_categories(['hide_empty' => false]);

        foreach ($all_categories as $cat) {
            if (!empty($category_buckets[$cat->term_id])) {
                $active_categories_data[] = [
                    'term' => $cat,
                    'posts' => $category_buckets[$cat->term_id]
                ];
            }
        }

        $total_groups = count($active_categories_data) + (!empty($uncategorized_posts) ? 1 : 0);

        if ($total_groups === 0) {
            return '<div class="notice notice-info inline"><p>Nenhum post encontrado na estrutura.</p></div>';
        }

        $html = '';

        if ($total_groups > 1) {
            
            foreach ($active_categories_data as $data) {
                $cat = $data['term'];
                $cat_posts = $data['posts'];

                $cat_color = $this->color_manager->get_color($cat->term_id);
                if (!$cat_color) $cat_color = '#646970'; 

                $style_border  = "border-left: 4px solid {$cat_color};";
                $style_title   = "color: {$cat_color};";
                $style_bg_soft = "background-color: " . $this->hex2rgba($cat_color, 0.03) . ";";

                $html .= '<div class="sults-category-folder" style="' . $style_border . ' ' . $style_bg_soft . '">';
                $html .= '<div class="sults-category-header" style="' . $style_title . '">
                            <span class="sults-cat-toggle dashicons dashicons-arrow-down-alt2"></span>
                            <span class="dashicons dashicons-category" style="margin-right:5px; opacity: 0.7;"></span> 
                            <strong>' . esc_html($cat->name) . '</strong>
                            <span class="count" style="color: #646970;">(' . count($cat_posts) . ')</span>
                          </div>';
                
                $html .= '<div class="sults-category-content">';
                $html .= '<ul class="sults-sortable-root" data-category-id="' . $cat->term_id . '">';
                foreach ($cat_posts as $root_post) {
                    $html .= $this->build_html_item($root_post, $posts_by_parent, $current_user_roles);
                }
                $html .= '</ul></div></div>';
            }

            if (!empty($uncategorized_posts)) {
                $html .= '<div class="sults-category-folder" style="border-left: 4px solid #646970; background-color: #f9f9f9;">';
                $html .= '<div class="sults-category-header" style="color: #444;">
                            <span class="sults-cat-toggle dashicons dashicons-arrow-down-alt2"></span>
                            <span class="dashicons dashicons-admin-generic" style="margin-right:5px; opacity: 0.7;"></span> 
                            <strong>Geral / Sem Categoria</strong>
                            <span class="count">(' . count($uncategorized_posts) . ')</span>
                          </div>';
                $html .= '<div class="sults-category-content">';
                $html .= '<ul class="sults-sortable-root" data-category-id="0">';
                foreach ($uncategorized_posts as $root_post) {
                    $html .= $this->build_html_item($root_post, $posts_by_parent, $current_user_roles);
                }
                $html .= '</ul></div></div>';
            }

        } else {
            if (!empty($active_categories_data)) {
                $data = $active_categories_data[0]; 
                $cat_id = $data['term']->term_id;
                $posts_to_render = $data['posts'];
            } else {
                $cat_id = 0;
                $posts_to_render = $uncategorized_posts;
            }

            $html .= '<ul class="sults-sortable-root" data-category-id="' . $cat_id . '">';
            foreach ($posts_to_render as $root_post) {
                $html .= $this->build_html_item($root_post, $posts_by_parent, $current_user_roles);
            }
            $html .= '</ul>';
        }

        return $html;
    }

    private function build_html_item( $element, $posts_by_parent, $user_roles ): string {
        $children = $posts_by_parent[$element->ID] ?? [];
        $has_children = !empty($children);

        $permalink = get_edit_post_link( $element->ID );
        $status_slug = $element->post_status;
        $status_obj = get_post_status_object( $status_slug );
        $status_label = $status_obj ? $status_obj->label : $status_slug;

        $is_redator = in_array( RoleDefinitions::REDATOR, $user_roles );

        if ( $is_redator ) {
            $current_user_id = get_current_user_id();
            $is_author       = ( (int) $element->post_author === $current_user_id );
            $is_public       = ( $status_slug === 'publish' );
            $is_finished     = ( $status_slug === 'finished' );

            $has_access = ($is_author || $is_public || $is_finished);
        } else {
            $has_access = true;
        }

        $card_class  = 'sults-card';
        $icon_class  = 'sults-action-icon';
        $link_html   = ''; 
        $action_html = ''; 

        if ( ! $has_access ) {
            $card_class .= ' disabled'; 
            $icon_class .= ' disabled';
            
            $link_html = '<span class="sults-card-title">' . esc_html( $element->post_title ) . '</span>';
            $action_html = '<span class="' . $icon_class . '" title="Acesso Restrito (Post de outro usuário)"><span class="dashicons dashicons-lock"></span></span>';

        } else {
            $is_locked_by_policy = $this->policy->is_editing_locked( $status_slug, $user_roles );
            $can_edit_native     = $this->user_provider->current_user_can( 'edit_post', $element->ID );

            if ( $is_locked_by_policy || ! $can_edit_native ) {
                $action_icon  = 'dashicons-visibility';
                $action_title = 'Visualizar (Apenas Leitura)';
                $target_url   = get_permalink( $element->ID ); 
            } else {
                $action_icon  = 'dashicons-edit';
                $action_title = 'Editar';
                $target_url   = $permalink; 
            }

            $link_html = '<a href="' . esc_url($target_url) . '" target="_blank" class="sults-card-title">' . esc_html( $element->post_title ) . '</a>';
            
            $action_html = '<a href="' . esc_url($target_url) . '" target="_blank" title="' . esc_attr($action_title) . '" class="' . $icon_class . '">
                                <span class="dashicons ' . $action_icon . '"></span>
                            </a>';
        }

        $toggle_html = $has_children 
            ? '<span class="sults-toggle dashicons dashicons-arrow-down-alt2"></span>' 
            : '<span class="sults-toggle-placeholder"></span>';

        $html = '<li class="sults-item" id="post-' . $element->ID . '" data-id="' . $element->ID . '">';
        
        $html .= '
            <div class="' . $card_class . '">
                ' . $toggle_html . '
                <div class="sults-card-left">
                    <span class="dashicons dashicons-move sults-handle"></span>
                    ' . $link_html . '
                </div>
                <div class="sults-card-right">
                        <span class="sults-status-badge sults-status-' . esc_attr( $status_slug ) . '">' . esc_html( $status_label ) . '</span>
                        ' . $action_html . '
                </div>
            </div>';


        $html .= '<ul class="sults-sortable-nested">';
        if ($has_children) {
            foreach ($children as $child) {
                $html .= $this->build_html_item($child, $posts_by_parent, $user_roles);
            }
        }
        $html .= '</ul>';

        $html .= '</li>';
        return $html;
    }

    private function hex2rgba($color, $opacity = false) {
        $default = 'rgb(0,0,0)';
        if(empty($color)) return $default; 
        if ($color[0] == '#' ) $color = substr( $color, 1 );
        if (strlen($color) == 6) {
                $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
        } elseif ( strlen( $color ) == 3 ) {
                $hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
        } else { return $default; }
        $rgb =  array_map('hexdec', $hex);
        if($opacity){
            if(abs($opacity) > 1) $opacity = 1.0;
            $output = 'rgba('.implode(",",$rgb).','.$opacity.')';
        } else {
            $output = 'rgb('.implode(",",$rgb).')';
        }
        return $output;
    }
}