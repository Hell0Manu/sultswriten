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
use Sults\Writen\Contracts\PostRepositoryInterface;
use Sults\Writen\Utils\HierarchyHelper;
use Sults\Writen\Utils\PathHelper;

class StructureManager implements HookableInterface {

	private WPUserProviderInterface $user_provider;
	private AssetLoaderInterface $asset_loader;
	private WPPostStatusProviderInterface $status_provider;
	private CategoryColorManager $color_manager;
	private WorkflowPolicy $policy;
	private PostRepositoryInterface $post_repository;

	public function __construct(
		WPUserProviderInterface $user_provider,
		AssetLoaderInterface $asset_loader,
		WPPostStatusProviderInterface $status_provider,
		CategoryColorManager $color_manager,
		WorkflowPolicy $policy,
		PostRepositoryInterface $post_repository
	) {
		$this->user_provider   = $user_provider;
		$this->asset_loader    = $asset_loader;
		$this->status_provider = $status_provider;
		$this->color_manager   = $color_manager;
		$this->policy          = $policy;
		$this->post_repository = $post_repository;
	}

	private function can_manage_structure(): bool {
		$user          = wp_get_current_user();
		$allowed_roles = array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE );
		return (bool) array_intersect( $allowed_roles, (array) $user->roles );
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		add_action( 'wp_ajax_sults_update_structure', array( $this, 'ajax_handle_move' ) );
		add_action( 'wp_ajax_sults_get_post_details', array( $this, 'ajax_get_post_details' ) );
		add_action( 'wp_ajax_sults_create_post', array( $this, 'ajax_create_post' ) );
		add_action( 'wp_ajax_sults_save_quick_edit', array( $this, 'ajax_save_quick_edit' ) );
	}

	public function register_menu(): void {
		add_menu_page(
			__( 'Estrutura', 'sultswriten' ),
			__( 'Estrutura', 'sultswriten' ),
			'edit_posts',
			'sults-writen-structure',
			array( $this, 'render_page' ),
			'dashicons-networking',
			30
		);
	}

	public function enqueue_assets( $hook ): void {
		if ( strpos( $hook, 'sults-writen-structure' ) === false ) {
			return;
		}

		wp_enqueue_style( 'sults-writen-variables' );
		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_style( 'sults-writen-status-css' );
		wp_enqueue_style( 'sults-writen-structure-css' );

		wp_add_inline_style(
			'sults-writen-structure-css',
			'
            .sults-card.disabled { opacity: 0.6; background: #fcfcfc; }
            .sults-card.disabled .sults-card-title { pointer-events: none; color: #a0a5aa; text-decoration: none; cursor: default; }
            .sults-card.disabled:hover { border-color: #e2e4e7; box-shadow: none; }
            .sults-action-icon.disabled { pointer-events: none; cursor: default; color: #d63638; }
            ul.sults-sortable-nested:empty { min-height: 10px; padding: 0; margin: 0; border: none; }
        '
		);

		if ( class_exists( StatusConfig::class ) ) {
			if ( class_exists( \Sults\Writen\Workflow\PostStatus\StatusVisuals::class ) ) {
				$status_css = \Sults\Writen\Workflow\PostStatus\StatusVisuals::get_css_rules();
			} else {
				$status_css = '';
			}

			if ( ! empty( $status_css ) ) {
				wp_add_inline_style( 'sults-writen-structure-css', $status_css );
			}
		}

		wp_enqueue_script( 'sults-writen-structure-js' );

		wp_localize_script(
			'sults-writen-structure-js',
			'sultsStructureParams',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'sults_structure_nonce' ),
				'can_manage' => $this->can_manage_structure(),
			)
		);
	}

	public function ajax_handle_move() {
		check_ajax_referer( 'sults_structure_nonce', 'security' );
		if ( ! $this->can_manage_structure() ) {
			wp_send_json_error( 'Sem permissão global.' );
		}

		$sults_post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! current_user_can( 'edit_post', $sults_post_id ) ) {
			wp_send_json_error( 'Você não tem permissão para mover este item.' );
		}

		$sults_parent_id = isset( $_POST['parent_id'] ) ? absint( $_POST['parent_id'] ) : 0;
		$order           = isset( $_POST['order'] ) ? array_map( 'absint', wp_unslash( $_POST['order'] ) ) : array();

		if ( $sults_post_id === $sults_parent_id ) {
			wp_send_json_error( 'Loop.' );
		}

		$this->post_repository->update(
			array(
				'ID'          => $sults_post_id,
				'post_parent' => $sults_parent_id,
			)
		);

		if ( ! empty( $order ) && is_array( $order ) ) {
			foreach ( $order as $index => $sibling_id ) {
				$this->post_repository->update(
					array(
						'ID'         => absint( $sibling_id ),
						'menu_order' => $index,
					)
				);
			}
		}
		wp_send_json_success();
	}

	public function ajax_get_post_details() {
		check_ajax_referer( 'sults_structure_nonce', 'security' );

		$sults_post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$sults_post    = $this->post_repository->find( $sults_post_id );

		if ( ! $sults_post ) {
			wp_send_json_error( 'Post não encontrado' );
		}

		$sults_author_id     = $sults_post->post_author;
		$sults_author_name   = get_the_author_meta( 'display_name', $sults_author_id );
		$sults_author_avatar = get_avatar_url( $sults_author_id, array( 'size' => 64 ) );

		$sults_status_slug = $sults_post->post_status;
		$sults_status_obj  = get_post_status_object( $sults_status_slug );
		$status_label      = $sults_status_obj ? $sults_status_obj->label : $sults_status_slug;
		$status_html       = sprintf(
			'<span class="sults-status-badge sults-status-%s">%s</span>',
			esc_attr( $sults_status_slug ),
			esc_html( $status_label )
		);

		$sults_cats     = get_the_category( $sults_post_id );
		$sults_cat_data = array(
			'name'  => 'Sem Categoria',
			'color' => '#ccc',
		);
		if ( ! empty( $sults_cats ) ) {
			$sults_primary_cat       = $sults_cats[0];
			$sults_cat_data['name']  = $sults_primary_cat->name;
			$sults_cat_data['color'] = $this->color_manager->get_color( $sults_primary_cat->term_id );
		}

		$relative_path = PathHelper::get_relative_path( $sults_post_id );

		$edit_link = get_edit_post_link( $sults_post_id, 'raw' );
		$view_link = get_permalink( $sults_post_id );

		$user_roles = $this->user_provider->get_current_user_roles();
		$can_edit   = ! $this->policy->is_editing_locked( $sults_status_slug, $user_roles ) && current_user_can( 'edit_post', $sults_post_id );

		$seo_title = get_post_meta( $sults_post_id, '_aioseo_title', true );
		$seo_desc  = get_post_meta( $sults_post_id, '_aioseo_description', true );

		if ( empty( $seo_title ) ) {
			$seo_title = get_the_title( $sults_post ) . ' - ' . get_bloginfo( 'name' );
		}
		if ( empty( $seo_desc ) ) {
			$seo_desc = get_the_excerpt( $sults_post );
			if ( empty( $seo_desc ) ) {
				$seo_desc = wp_trim_words( strip_shortcodes( $sults_post->post_content ), 25 );
			}
		}

		$response = array(
			'id'          => $sults_post_id,
			'title'       => get_the_title( $sults_post ),
			'slug'        => $sults_post->post_name,
			'status'      => $sults_status_slug,
			'status_html' => $status_html,
			'author'      => array(
				'id'     => $sults_author_id,
				'name'   => $sults_author_name,
				'avatar' => $sults_author_avatar,
			),
			'date'        => get_the_date( 'Y-m-d\TH:i', $sults_post ),
			'category'    => array_merge( $sults_cat_data, array( 'id' => ! empty( $sults_cats ) ? $sults_cats[0]->term_id : 0 ) ),
			'parent_id'   => $sults_post->post_parent,
			'password'    => $sults_post->post_password,
			'path'        => $relative_path,
			'seo'         => array(
				'title'       => $seo_title,
				'description' => $seo_desc,
			),
			'links'       => array(
				'edit'     => $edit_link,
				'view'     => $view_link,
				'can_edit' => $can_edit,
			),
		);

		wp_send_json_success( $response );
	}


	public function ajax_create_post() {
		check_ajax_referer( 'sults_structure_nonce', 'security' );

		if ( ! $this->can_manage_structure() ) {
			wp_send_json_error( 'Sem permissão.' );
		}

		$title           = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$sults_parent_id = isset( $_POST['parent_id'] ) ? absint( $_POST['parent_id'] ) : 0;
		$sults_cat_id    = isset( $_POST['cat_id'] ) ? absint( $_POST['cat_id'] ) : 0;
		$slug            = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';

		if ( empty( $title ) ) {
			wp_send_json_error( 'O título é obrigatório.' );
		}

		$sults_post_data = array(
			'post_title'  => $title,
			'post_name'   => $slug,
			'post_status' => 'draft',
			'post_type'   => 'post',
			'post_parent' => $sults_parent_id,
		);

		$sults_post_id = $this->post_repository->create( $sults_post_data );

		if ( is_wp_error( $sults_post_id ) ) {
			wp_send_json_error( $sults_post_id->get_error_message() );
		}

		if ( $sults_cat_id > 0 ) {
			$this->post_repository->set_terms( $sults_post_id, array( $sults_cat_id ), 'category' );
		}

		$redirect_url = get_edit_post_link( $sults_post_id, 'raw' );

		wp_send_json_success(
			array(
				'id'           => $sults_post_id,
				'redirect_url' => $redirect_url,
			)
		);
	}

	public function ajax_save_quick_edit() {
		check_ajax_referer( 'sults_structure_nonce', 'security' );

		$sults_post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! current_user_can( 'edit_post', $sults_post_id ) ) {
			wp_send_json_error( 'Sem permissão para editar este post.' );
		}

		$sults_post_data = array(
			'ID'            => $sults_post_id,
			'post_title'    => isset( $_POST['post_title'] ) ? sanitize_text_field( wp_unslash( $_POST['post_title'] ) ) : '',
			'post_name'     => isset( $_POST['post_name'] ) ? sanitize_title( wp_unslash( $_POST['post_name'] ) ) : '',
			'post_status'   => isset( $_POST['post_status'] ) ? sanitize_text_field( wp_unslash( $_POST['post_status'] ) ) : 'draft',
			'post_author'   => isset( $_POST['post_author'] ) ? absint( $_POST['post_author'] ) : get_current_user_id(),
			'post_parent'   => isset( $_POST['post_parent'] ) ? absint( $_POST['post_parent'] ) : 0,
			'post_password' => isset( $_POST['post_password'] ) ? sanitize_text_field( wp_unslash( $_POST['post_password'] ) ) : '',
			'post_date'     => isset( $_POST['post_date'] ) ? sanitize_text_field( wp_unslash( $_POST['post_date'] ) ) : '',
		);

		$updated_id = $this->post_repository->update( $sults_post_data );

		if ( is_wp_error( $updated_id ) ) {
			wp_send_json_error( $updated_id->get_error_message() );
		}

		$sults_cat_id = isset( $_POST['post_category'] ) ? absint( $_POST['post_category'] ) : 0;
		$this->post_repository->set_terms( $sults_post_id, $sults_cat_id > 0 ? array( $sults_cat_id ) : array(), 'category' );

		wp_send_json_success( 'Post atualizado com sucesso.' );
	}

	public function render_page(): void {
		$sults_tree_html = $this->get_tree_html();

		$sults_categories = get_categories( array( 'hide_empty' => false ) );
		$sults_categories = HierarchyHelper::build_hierarchy( $sults_categories, 0, 0, 'term_id', 'parent' );

		$sults_authors      = get_users(
			array(
				'capability__in' => array( 'edit_posts' ),
				'orderby'        => 'display_name',
			)
		);
		$sults_all_statuses = $this->status_provider->get_all_status_slugs();

		$raw_parents = $this->post_repository->get_all_for_parents();

		$sults_potential_parents = HierarchyHelper::build_hierarchy( $raw_parents );

		$view_path = plugin_dir_path( dirname( dirname( __DIR__ ) ) . '/sults-writen.php' ) . 'src/Interface/Dashboard/views/structure-page.php';

		if ( file_exists( $view_path ) ) {
			include $view_path;
		} else {
			echo '<div class="error"><p>Erro: Arquivo de view não encontrado em ' . esc_html( $view_path ) . '</p></div>';
		}
	}

	/**
	 * Gera o HTML da árvore de posts de forma hierárquica (Pastas dentro de Pastas).
	 */
	private function get_tree_html(): string {

		$statuses           = $this->status_provider->get_all_status_slugs();
		$sults_posts        = $this->post_repository->get_by_status( $statuses );
		$current_user_roles = $this->user_provider->get_current_user_roles();

		$sults_posts_by_parent = array();
		$all_posts_map         = array();
		foreach ( $sults_posts as $sults_p ) {
			$all_posts_map[ $sults_p->ID ]                    = $sults_p;
			$sults_posts_by_parent[ $sults_p->post_parent ][] = $sults_p;
		}

		foreach ( $sults_posts as $sults_post ) {
			if ( $sults_post->post_parent > 0 && ! isset( $all_posts_map[ $sults_post->post_parent ] ) ) {
				$sults_post->post_parent    = 0;
				$sults_posts_by_parent[0][] = $sults_post;
			}
		}

		$root_posts             = $sults_posts_by_parent[0] ?? array();
		$sults_category_buckets = array();
		$uncategorized_posts    = array();

		foreach ( $root_posts as $sults_post ) {
			$sults_cats = get_the_category( $sults_post->ID );
			if ( empty( $sults_cats ) ) {
				$uncategorized_posts[] = $sults_post;
			} else {
				$sults_primary_cat                                       = $sults_cats[0];
				$sults_category_buckets[ $sults_primary_cat->term_id ][] = $sults_post;
			}
		}

		$all_categories  = get_categories( array( 'hide_empty' => false ) );
		$sults_cat_index = array();
		$sults_cat_tree  = array();

		foreach ( $all_categories as $sults_cat ) {
			$sults_cat->children                    = array(); // @phpstan-ignore-line
			$sults_cat->posts                       = $sults_category_buckets[ $sults_cat->term_id ] ?? array(); // @phpstan-ignore-line
			$sults_cat_index[ $sults_cat->term_id ] = $sults_cat;
		}

		foreach ( $all_categories as $sults_cat ) {
			if ( $sults_cat->parent > 0 && isset( $sults_cat_index[ $sults_cat->parent ] ) ) {
				$sults_cat_index[ $sults_cat->parent ]->children[] = $sults_cat; // @phpstan-ignore-line
			} else {
				$sults_cat_tree[] = $sults_cat;
			}
		}

		$html = '';

		foreach ( $sults_cat_tree as $root_cat ) {
			$html .= $this->render_category_node( $root_cat, $sults_posts_by_parent, $current_user_roles );
		}

		if ( ! empty( $uncategorized_posts ) ) {
			$html .= $this->render_uncategorized_folder( $uncategorized_posts, $sults_posts_by_parent, $current_user_roles );
		}

		if ( empty( $html ) ) {
			return '<div class="notice notice-info inline"><p>Nenhum post encontrado na estrutura.</p></div>';
		}

		return $html;
	}

	/**
	 * Renderiza uma pasta de categoria e, recursivamente, seus filhos.
	 */
	private function render_category_node( $sults_cat, $sults_posts_by_parent, $user_roles ): string {
		$children_html = '';
		foreach ( $sults_cat->children as $child ) {
			$children_html .= $this->render_category_node( $child, $sults_posts_by_parent, $user_roles );
		}

		if ( empty( $sults_cat->posts ) && empty( $children_html ) ) {
			return '';
		}

		$sults_cat_color = $this->color_manager->get_color( $sults_cat->term_id );
		if ( ! $sults_cat_color ) {
			$sults_cat_color = '#646970';
		}

		$sults_style_border  = "border-left: 4px solid {$sults_cat_color};";
		$sults_style_title   = "color: {$sults_cat_color};";
		$sults_style_bg_soft = 'background-color: ' . $this->hex2rgba( $sults_cat_color, 0.03 ) . ';';

		$html = '<div class="sults-category-folder" style="' . $sults_style_border . ' ' . $sults_style_bg_soft . ' margin-bottom: 15px;">';

		// Cabeçalho.
		$html .= '<div class="sults-category-header" style="' . $sults_style_title . '">
                    <span class="sults-cat-toggle dashicons dashicons-arrow-down-alt2"></span>
                    <span class="dashicons dashicons-category" style="margin-right:5px; opacity: 0.7;"></span> 
                    <strong>' . esc_html( $sults_cat->name ) . '</strong>
                    <span class="count" style="color: #646970;">(' . count( $sults_cat->posts ) . ')</span>
                  </div>';

		$html .= '<div class="sults-category-content">';

		if ( ! empty( $sults_cat->posts ) ) {
			$html .= '<ul class="sults-sortable-root" data-category-id="' . $sults_cat->term_id . '">';
			foreach ( $sults_cat->posts as $root_post ) {
				$html .= $this->build_html_item( $root_post, $sults_posts_by_parent, $user_roles );
			}
			$html .= '</ul>';
		}

		if ( ! empty( $children_html ) ) {
			$html .= '<div class="sults-subcategories" style="padding-left: 25px; border-left: 1px dashed #ddd; margin-left: 5px; margin-top: 10px;">';
			$html .= $children_html;
			$html .= '</div>';
		}

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Renderiza a pasta especial "Sem Categoria".
	 */
	private function render_uncategorized_folder( $sults_posts, $sults_posts_by_parent, $user_roles ): string {
		$html = '<div class="sults-category-folder" style="border-left: 4px solid #646970; background-color: #f9f9f9; margin-bottom: 15px;">';

		$html .= '<div class="sults-category-header" style="color: #444;">
                    <span class="sults-cat-toggle dashicons dashicons-arrow-down-alt2"></span>
                    <span class="dashicons dashicons-admin-generic" style="margin-right:5px; opacity: 0.7;"></span> 
                    <strong>Geral / Sem Categoria</strong>
                    <span class="count">(' . count( $sults_posts ) . ')</span>
                  </div>';

		$html .= '<div class="sults-category-content">';
		$html .= '<ul class="sults-sortable-root" data-category-id="0">';

		foreach ( $sults_posts as $root_post ) {
			$html .= $this->build_html_item( $root_post, $sults_posts_by_parent, $user_roles );
		}

		$html .= '</ul></div></div>';

		return $html;
	}

	private function build_html_item( $element, $sults_posts_by_parent, $user_roles ): string {
		$children     = $sults_posts_by_parent[ $element->ID ] ?? array();
		$has_children = ! empty( $children );

		$sults_permalink   = get_edit_post_link( $element->ID );
		$sults_status_slug = $element->post_status;
		$sults_status_obj  = get_post_status_object( $sults_status_slug );
		$status_label      = $sults_status_obj ? $sults_status_obj->label : $sults_status_slug;

		$is_redator = in_array( RoleDefinitions::REDATOR, $user_roles, true );

		if ( $is_redator ) {
			$current_user_id = get_current_user_id();
			$is_author       = ( (int) $element->post_author === $current_user_id );
			$is_public       = ( $sults_status_slug === 'publish' );

			$has_access = ( $is_author || $is_public );
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

			$link_html   = '<span class="sults-card-title">' . esc_html( $element->post_title ) . '</span>';
			$action_html = '<span class="' . $icon_class . '" title="Acesso Restrito (Post de outro usuário)"><span class="dashicons dashicons-lock"></span></span>';

		} else {
			$is_locked_by_policy = $this->policy->is_editing_locked( $sults_status_slug, $user_roles );
			$can_edit_native     = $this->user_provider->current_user_can( 'edit_post', $element->ID );

			if ( $is_locked_by_policy || ! $can_edit_native ) {
				$action_icon  = 'dashicons-visibility';
				$action_title = 'Visualizar (Apenas Leitura)';
				$target_url   = get_permalink( $element->ID );
			} else {
				$action_icon  = 'dashicons-edit';
				$action_title = 'Editar';
				$target_url   = $sults_permalink;
			}

			$link_html = '<a href="' . esc_url( $target_url ) . '" target="_blank" class="sults-card-title">' . esc_html( $element->post_title ) . '</a>';

			$action_html = '<a href="' . esc_url( $target_url ) . '" target="_blank" title="' . esc_attr( $action_title ) . '" class="' . $icon_class . '">
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
                        <span class="sults-status-badge sults-status-' . esc_attr( $sults_status_slug ) . '">' . esc_html( $status_label ) . '</span>
                        ' . $action_html . '
                </div>
            </div>';

		$html .= '<ul class="sults-sortable-nested">';
		if ( $has_children ) {
			foreach ( $children as $child ) {
				$html .= $this->build_html_item( $child, $sults_posts_by_parent, $user_roles );
			}
		}
		$html .= '</ul>';

		$html .= '</li>';
		return $html;
	}

	private function hex2rgba( $color, $opacity = false ) {
		$default = 'rgb(0,0,0)';
		if ( empty( $color ) ) {
			return $default;
		}
		if ( $color[0] === '#' ) {
			$color = substr( $color, 1 );
		}
		if ( strlen( $color ) === 6 ) {
				$hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
		} elseif ( strlen( $color ) === 3 ) {
				$hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
		} else {
			return $default; }
		$rgb = array_map( 'hexdec', $hex );
		if ( $opacity ) {
			if ( abs( $opacity ) > 1 ) {
				$opacity = 1.0;
			}
			$output = 'rgba(' . implode( ',', $rgb ) . ',' . $opacity . ')';
		} else {
			$output = 'rgb(' . implode( ',', $rgb ) . ')';
		}
		return $output;
	}
}
