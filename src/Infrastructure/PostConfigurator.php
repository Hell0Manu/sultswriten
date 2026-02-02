<?php
/**
 * Configurador do Tipo de Post (Post Type).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */

namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\HookableInterface;

/**
 * Classe PostConfigurator.
 *
 * Responsável por modificar o comportamento padrão do Post Type 'post' do WordPress.
 *
 * Principais alterações:
 * 1. Habilita hierarquia (transforma posts em árvores pai/filho).
 * 2. Adiciona suporte a atributos de página (ordem e seleção de parente).
 * 3. Impõe um limite rígido de profundidade na hierarquia (máximo 3 níveis) para
 * evitar estruturas complexas demais para exportação.
 *
 * @see HookableInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @author     Sults
 * @since      0.1.0
 */
class PostConfigurator implements HookableInterface {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		// Modifica os argumentos de registro do post type 'post'.
		add_filter( 'register_post_type_args', array( $this, 'configure_post_type' ), 20, 2 );
		
		// Adiciona suporte a atributos de página (necessário para dropdown de "Pai").
		add_action( 'init', array( $this, 'add_page_attributes_support' ) );
		
		// Valida e corrige a hierarquia antes de salvar no banco.
		add_filter( 'wp_insert_post_data', array( $this, 'enforce_hierarchy_limit' ), 10, 2 );
	}

	/**
	 * Configura os argumentos do Post Type.
	 *
	 * Habilita 'hierarchical' => true e customiza os labels para refletir
	 * uma estrutura mais organizacional.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $args            Argumentos originais do post type.
	 * @param string $sults_post_type O slug do post type sendo registrado.
	 * @return array Argumentos modificados.
	 */
	public function configure_post_type( array $args, string $sults_post_type ): array {
		if ( 'post' !== $sults_post_type ) {
			return $args;
		}

		// Transforma posts em estrutura de árvore.
		$args['hierarchical'] = true;

		$sults_labels = array(
			'name'              => 'Posts',
			'singular_name'     => 'Post',
			'add_new'           => 'Adicionar Novo',
			'add_new_item'      => 'Adicionar Novo Post',
			'edit_item'         => 'Editar Post',
			'new_item'          => 'Novo Post',
			'view_item'         => 'Ver Post',
			'view_items'        => 'Ver Posts',
			'search_items'      => 'Pesquisar Posts',
			'not_found'         => 'Nenhum post encontrado',
			'not_found_in_trash'=> 'Nenhum post encontrado na lixeira',
			'parent_item_colon' => 'Post Pai:',
			'all_items'         => 'Todos os Posts',
			'archives'          => 'Arquivos de Post',
			'attributes'        => 'Atributos do Post',
			'insert_into_item'  => 'Inserir no post',
			'uploaded_to_this_item' => 'Enviado para este post',
			'menu_name'         => 'Posts',
			'name_admin_bar'    => 'Post',
		);

		$args['labels'] = array_merge( (array) $args['labels'], $sults_labels );

		return $args;
	}

	/**
	 * Adiciona suporte a 'page-attributes'.
	 *
	 * Isso habilita a caixa "Atributos do Post" no editor, permitindo
	 * definir a ordem (menu_order) e o post pai.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function add_page_attributes_support(): void {
		add_post_type_support( 'post', 'page-attributes' );
	}

	/**
	 * Impõe o limite de hierarquia ao salvar o post.
	 *
	 * Regra: Máximo de 3 níveis (Raiz -> Filho -> Neto).
	 * Se o usuário tentar mover um post para um nível 4 (Bisneto),
	 * o sistema automaticamente o move para cima na árvore.
	 *
	 * Também impede que uma árvore inteira seja movida se a soma da profundidade
	 * dos filhos com o novo pai exceder o limite.
	 *
	 * @since 0.1.0
	 *
	 * @param array $data    Dados do post a serem salvos (slashados).
	 * @param array $postarr Dados brutos do post (incluindo ID para updates).
	 * @return array Dados modificados.
	 */
	public function enforce_hierarchy_limit( array $data, array $postarr ): array {
		// Apenas para 'post' e se tiver um pai definido.
		if ( 'post' !== $data['post_type'] || empty( $data['post_parent'] ) ) {
			return $data;
		}

		$parent_id = (int) $data['post_parent'];
		$post_id   = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;

		// Previne auto-referência (ser pai de si mesmo).
		if ( $post_id > 0 && $post_id === $parent_id ) {
			$data['post_parent'] = 0;
			return $data;
		}

		// Checa a profundidade do NOVO pai.
		$ancestors = get_post_ancestors( $parent_id );

		// Se o pai já tem 2 ancestrais (ele é nível 3), não pode ter filhos.
		if ( count( $ancestors ) >= 2 ) {
			// Solução: Move o post para ser irmão do pai atual (sobe um nível).
			if ( ! empty( $ancestors ) ) {
				$data['post_parent'] = $ancestors[0]; 
			} else {
				$data['post_parent'] = 0;
			}
		}

		// Validação reversa: Se estou movendo um post que JÁ TEM filhos.
		if ( $post_id > 0 ) {
			 $max_child_depth = $this->get_max_children_depth( $post_id );
			 
			 $new_parent_ancestors = get_post_ancestors( $data['post_parent'] );
			 $parent_depth = count( $new_parent_ancestors ) + 1; // +1 conta o próprio pai.
			 
			 // Se (Profundidade do destino + Profundidade da minha árvore) > Limite(2 indexados = 3 níveis).
			 if ( ( $parent_depth + $max_child_depth ) > 2 ) {
				 // Reseta para raiz para evitar quebra da árvore.
				 $data['post_parent'] = 0;
			 }
		}

		return $data;
	}

	/**
	 * Calcula a profundidade máxima dos filhos de um post recursivamente.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id O ID do post pai.
	 * @return int A profundidade máxima encontrada (0 se não tiver filhos).
	 */
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
			// Recursão: 1 nível atual + profundidade do filho.
			$depth = 1 + $this->get_max_children_depth( $child_id );
			if ( $depth > $max_depth ) {
				$max_depth = $depth;
			}
		}
		return $max_depth;
	}
}