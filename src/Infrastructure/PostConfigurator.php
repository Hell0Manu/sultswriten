<?php
namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\HookableInterface;

class PostConfigurator implements HookableInterface {

	public function register(): void {
		add_filter( 'register_post_type_args', array( $this, 'configure_post_type' ), 20, 2 );
		add_action( 'init', array( $this, 'add_page_attributes_support' ) );
		add_filter( 'wp_insert_post_data', array( $this, 'enforce_hierarchy_limit' ), 10, 2 );
	}

	public function configure_post_type( array $args, string $sults_post_type ): array {
		if ( 'post' !== $sults_post_type ) {
			return $args;
		}

		$args['hierarchical'] = true;

		$sults_labels = array(
			'name'                  => 'Posts',
			'singular_name'         => 'Post',
			'add_new'               => 'Adicionar Novo',
			'add_new_item'          => 'Adicionar Novo Post',
			'edit_item'             => 'Editar Post',
			'new_item'              => 'Novo Post',
			'view_item'             => 'Ver Post',
			'view_items'            => 'Ver Posts',
			'search_items'          => 'Pesquisar Posts',
			'not_found'             => 'Nenhum post encontrado',
			'not_found_in_trash'    => 'Nenhum post encontrado na lixeira',
			'parent_item_colon'     => 'Post Pai:',
			'all_items'             => 'Todos os Posts',
			'archives'              => 'Arquivos de Post',
			'attributes'            => 'Atributos do Post',
			'insert_into_item'      => 'Inserir no post',
			'uploaded_to_this_item' => 'Enviado para este post',
			'menu_name'             => 'Posts',
			'name_admin_bar'        => 'Post',
		);

		$args['labels'] = array_merge( (array) $args['labels'], $sults_labels );

		return $args;
	}

	public function add_page_attributes_support(): void {
		add_post_type_support( 'post', 'page-attributes' );
	}

	/**
	 * Valida globalmente o limite de hierarquia.
	 * Se tentar ir para o nível 4, força o nível 3 (atribuindo ao avô).
	 */
	public function enforce_hierarchy_limit( array $data, array $postarr ): array {
		if ( 'post' !== $data['post_type'] || empty( $data['post_parent'] ) ) {
			return $data;
		}

		$parent_id = (int) $data['post_parent'];
		$post_id   = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;

		if ( $post_id > 0 && $post_id === $parent_id ) {
			$data['post_parent'] = 0;
			return $data;
		}

		$ancestors = get_post_ancestors( $parent_id );

		if ( count( $ancestors ) >= 2 ) {
			
			if ( ! empty( $ancestors ) ) {
				$data['post_parent'] = $ancestors[0]; 
			} else {
				$data['post_parent'] = 0;
			}
		}

		if ( $post_id > 0 ) {
			 $max_child_depth = $this->get_max_children_depth( $post_id );
			 
			 $new_parent_ancestors = get_post_ancestors( $data['post_parent'] );
			 $parent_depth = count( $new_parent_ancestors ) + 1; 
			 
			 if ( ( $parent_depth + $max_child_depth ) > 2 ) {
				 $data['post_parent'] = 0;
			 }
		}

		return $data;
	}

	private function get_max_children_depth( int $post_id ): int {
		$children = get_children( array(
			'post_parent' => $post_id,
			'post_type'   => 'post',
			'fields'      => 'ids',
		) );

		if ( empty( $children ) ) {
			return 0;
		}

		$max_depth = 0;
		foreach ( $children as $child_id ) {
			$depth = 1 + $this->get_max_children_depth( $child_id );
			if ( $depth > $max_depth ) {
				$max_depth = $depth;
			}
		}
		return $max_depth;
	}
}