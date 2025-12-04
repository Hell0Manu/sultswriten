<?php
/**
 * Responsável pela apresentação visual na listagem de posts do Admin.
 *
 * Adiciona colunas personalizadas de status, filtros avançados (Status/Autor)
 * e indicadores visuais (badges) na tabela padrão do WordPress (edit.php).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\PostStatus
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\PostStatus;

use WP_Post;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;

class PostListPresenter {

	private WPPostStatusProviderInterface $status_provider;
	private WPUserProviderInterface $user_provider;

	public function __construct(
		WPPostStatusProviderInterface $status_provider,
		WPUserProviderInterface $user_provider
	) {
		$this->status_provider = $status_provider;
		$this->user_provider   = $user_provider;
	}

	public function register(): void {
		if ( is_admin() ) {
			add_filter( 'display_post_states', array( $this, 'display_states' ), 10, 2 );
			add_filter( 'manage_post_posts_columns', array( $this, 'add_status_column_header' ) );
			add_action( 'manage_post_posts_custom_column', array( $this, 'fill_status_column_content' ), 10, 2 );
			add_action( 'restrict_manage_posts', array( $this, 'add_custom_filters_to_post_list' ) );
		}
	}

	public function add_status_column_header( array $columns ): array {
		$new = array();
		foreach ( $columns as $key => $value ) {
			$new[ $key ] = $value;
			if ( 'cb' === $key ) {
				$new['post_status_custom'] = __( 'Status', 'sultswriten' );
			}
		}
		return $new;
	}

	public function fill_status_column_content( string $column, int $post_id ): void {
		if ( 'post_status_custom' !== $column ) {
			return;
		}

		$status_slug = $this->status_provider->get_status( $post_id );
		$status_obj  = $this->status_provider->get_status_object( $status_slug );

		$label = ( $status_obj && isset( $status_obj->label ) ) ? $status_obj->label : $status_slug;

		printf(
			'<span class="sults-status-badge sults-status-%s">%s</span>',
			esc_attr( $status_slug ),
			esc_html( $label )
		);
	}

	public function display_states( array $states, WP_Post $post ): array {
		$status          = $post->post_status;
		$custom_statuses = PostStatusRegistrar::get_custom_statuses();

		if ( isset( $custom_statuses[ $status ] ) ) {
			$states[] = esc_html( $custom_statuses[ $status ] );
		}

		return $states;
	}

	public function add_custom_filters_to_post_list( string $post_type ): void {
		if ( 'post' !== $post_type ) {
			return;
		}

		$current_status = $this->get_get_param( 'post_status' );

		echo '<select name="post_status" id="filter-by-status">';
		echo '<option value="">' . esc_html__( 'Todos os Status', 'sultswriten' ) . '</option>';

		$standard_slugs = array( 'publish', 'draft', 'pending' );

		foreach ( $standard_slugs as $slug ) {
			$obj = $this->status_provider->get_status_object( $slug );
			if ( $obj ) {
				printf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $slug ),
					selected( $current_status, $slug, false ),
					esc_html( $obj->label )
				);
			}
		}

		$custom_statuses = PostStatusRegistrar::get_custom_statuses();
		foreach ( $custom_statuses as $slug => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $slug ),
				selected( $current_status, $slug, false ),
				esc_html( $label )
			);
		}

		echo '</select>';

		$current_author = absint( $this->get_get_param( 'author' ) );

		echo wp_kses(
			$this->user_provider->get_users_dropdown(
				array(
					'show_option_all'  => __( 'Todos os Autores', 'sultswriten' ),
					'name'             => 'author',
					'selected'         => $current_author,
					'include_selected' => true,
				)
			),
			array(
				'select' => array(
					'name'  => array(),
					'id'    => array(),
					'class' => array(),
				),
				'option' => array(
					'value'    => array(),
					'selected' => array(),
				),
			)
		);
	}

	protected function get_get_param( string $key ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET[ $key ] ) ) {
			return '';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
	}
}