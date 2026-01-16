<?php
namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\HookableInterface;

class PostConfigurator implements HookableInterface {

	public function register(): void {
		add_filter( 'register_post_type_args', array( $this, 'configure_post_type' ), 20, 2 );
		add_action( 'init', array( $this, 'add_page_attributes_support' ) );
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

	/**
	 * Adiciona suporte a atributos de página (Ordem, Pai/Filho) aos Posts.
	 * Isso corrige o erro fatal.
	 */
	public function add_page_attributes_support(): void {
		add_post_type_support( 'post', 'page-attributes' );
	}
}
