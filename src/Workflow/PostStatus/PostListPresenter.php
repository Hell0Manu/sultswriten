<?php
/**
 * Apresentador da Listagem de Posts (Admin).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\PostStatus
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\PostStatus;

use WP_Post;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;

/**
 * Classe PostListPresenter.
 *
 * Responsável por personalizar a tabela de listagem de posts no Admin do WordPress (`edit.php`).
 *
 * Funcionalidades:
 * 1. Remove colunas desnecessárias (Tags, Comentários) para limpar a interface.
 * 2. Adiciona uma coluna personalizada de "Status" com badges visuais.
 * 3. Adiciona filtros avançados (Dropdown de Status Personalizados e Dropdown de Autores) na barra de ferramentas.
 * 4. Exibe o estado do post logo após o título (ex: " - Rascunho").
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\PostStatus
 * @author     Sults
 * @since      0.1.0
 */
class PostListPresenter {

	/**
	 * Provedor de dados de status.
	 * @var WPPostStatusProviderInterface
	 */
	private WPPostStatusProviderInterface $status_provider;

	/**
	 * Provedor de dados de usuário.
	 * @var WPUserProviderInterface
	 */
	private WPUserProviderInterface $user_provider;

	/**
	 * Construtor.
	 *
	 * @param WPPostStatusProviderInterface $status_provider Serviço para buscar objetos de status.
	 * @param WPUserProviderInterface       $user_provider   Serviço para gerar dropdown de autores.
	 */
	public function __construct(
		WPPostStatusProviderInterface $status_provider,
		WPUserProviderInterface $user_provider
	) {
		$this->status_provider = $status_provider;
		$this->user_provider   = $user_provider;
	}

	/**
	 * Registra os hooks de personalização da tabela.
	 *
	 * Executa apenas no ambiente administrativo (`is_admin()`).
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		if ( is_admin() ) {
			// Adiciona o label do estado logo após o título do post.
			add_filter( 'display_post_states', array( $this, 'display_states' ), 10, 2 );
			
			// Manipulação de colunas (Adicionar cabeçalho e remover indesejadas).
			add_filter( 'manage_post_posts_columns', array( $this, 'add_status_column_header' ) );
			add_filter( 'manage_post_posts_columns', array( $this, 'remove_unwanted_columns' ), 20 );
			
			// Preencher o conteúdo da coluna personalizada.
			add_action( 'manage_post_posts_custom_column', array( $this, 'fill_status_column_content' ), 10, 2 );
			
			// Adicionar filtros dropdown no topo da tabela.
			add_action( 'restrict_manage_posts', array( $this, 'add_custom_filters_to_post_list' ) );
		}
	}

	/**
	 * Remove colunas nativas que não são utilizadas no workflow.
	 *
	 * Remove 'tags' e 'comments' para deixar a tabela mais limpa e focada.
	 *
	 * @since 0.1.0
	 * @param array $columns Colunas atuais da tabela.
	 * @return array Colunas filtradas.
	 */
	public function remove_unwanted_columns( array $columns ): array {
		if ( isset( $columns['tags'] ) ) {
			unset( $columns['tags'] );
		}

		if ( isset( $columns['comments'] ) ) {
			unset( $columns['comments'] );
		}

		return $columns;
	}

	/**
	 * Adiciona o cabeçalho da coluna de Status.
	 *
	 * Insere a coluna 'Status' logo após o checkbox ('cb'), movendo as outras para a direita.
	 *
	 * @since 0.1.0
	 * @param array $columns Colunas atuais.
	 * @return array Colunas com a nova entrada 'post_status_custom'.
	 */
	public function add_status_column_header( array $columns ): array {
		$new = array();
		foreach ( $columns as $key => $value ) {
			$new[ $key ] = $value;
			// Insere logo após o checkbox de seleção em massa.
			if ( 'cb' === $key ) {
				$new['post_status_custom'] = __( 'Status', 'sultswriten' );
			}
		}
		return $new;
	}

	/**
	 * Renderiza o conteúdo da coluna de Status para cada linha (post).
	 *
	 * Gera um badge HTML (`<span class="sults-status-badge ...">`) com a cor e o label do status.
	 *
	 * @since 0.1.0
	 * @param string $column        Nome da coluna sendo processada.
	 * @param int    $sults_post_id ID do post da linha atual.
	 * @return void
	 */
	public function fill_status_column_content( string $column, int $sults_post_id ): void {
		if ( 'post_status_custom' !== $column ) {
			return;
		}

		$sults_status_slug = $this->status_provider->get_status( $sults_post_id );
		$sults_status_obj  = $this->status_provider->get_status_object( $sults_status_slug );

		// Fallback: Se o objeto não existir, usa o slug como label.
		$sults_label = ( $sults_status_obj && isset( $sults_status_obj->label ) ) ? $sults_status_obj->label : $sults_status_slug;

		printf(
			'<span class="sults-status-badge sults-status-%s">%s</span>',
			esc_attr( $sults_status_slug ),
			esc_html( $sults_label )
		);
	}

	/**
	 * Exibe os estados do post ao lado do título (ex: " - Rascunho", " - Protegido").
	 *
	 * Adiciona o label do status personalizado à lista de estados exibidos pelo WP.
	 *
	 * @since 0.1.0
	 * @param array   $states     Lista de estados atuais (ex: ['Privado']).
	 * @param WP_Post $sults_post Objeto do post.
	 * @return array Lista de estados atualizada.
	 */
	public function display_states( array $states, WP_Post $sults_post ): array {
		$status          = $sults_post->post_status;
		$custom_statuses = PostStatusRegistrar::get_custom_statuses();

		if ( isset( $custom_statuses[ $status ] ) ) {
			$states[] = esc_html( $custom_statuses[ $status ] );
		}

		return $states;
	}

	/**
	 * Adiciona filtros dropdown na barra de ferramentas da listagem.
	 *
	 * 1. Dropdown de Status: Inclui status nativos e personalizados.
	 * 2. Dropdown de Autores: Permite filtrar por criador do post.
	 *
	 * @since 0.1.0
	 * @param string $sults_post_type O tipo de post da tela atual.
	 * @return void
	 */
	public function add_custom_filters_to_post_list( string $sults_post_type ): void {
		// Aplica apenas para o tipo 'post'.
		if ( 'post' !== $sults_post_type ) {
			return;
		}

		// --- FILTRO DE STATUS ---
		
		$input_status   = $this->get_get_param( 'post_status' );
		$current_status = '';

		// Valida o status recebido via GET.
		$valid_statuses = array_merge(
			array( 'publish', 'draft', 'pending' ),
			array_keys( PostStatusRegistrar::get_custom_statuses() )
		);

		if ( in_array( $input_status, $valid_statuses, true ) ) {
			$current_status = $input_status;
		}

		echo '<select name="post_status" id="filter-by-status">';
		echo '<option value="">' . esc_html__( 'Todos os Status', 'sultswriten' ) . '</option>';

		// Status Nativos.
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

		// Status Personalizados.
		$custom_statuses = PostStatusRegistrar::get_custom_statuses();
		foreach ( $custom_statuses as $slug => $sults_label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $slug ),
				selected( $current_status, $slug, false ),
				esc_html( $sults_label )
			);
		}
		echo '</select>';

		// --- FILTRO DE AUTOR ---

		$current_author = absint( $this->get_get_param( 'author' ) );

		// Usa o WPUserProvider para gerar o HTML do dropdown de forma segura.
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

	/**
	 * Recupera e sanitiza parâmetros da URL ($_GET).
	 *
	 * @since 0.1.0
	 * @param string $key Chave do parâmetro.
	 * @return string Valor sanitizado ou string vazia.
	 */
	protected function get_get_param( string $key ): string {
		// Nonce verification não é necessário aqui pois é apenas leitura de filtro visual.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET[ $key ] ) ) {
			return '';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
	}
}
