<?php
/**
 * Gerenciador de Cores de Categoria.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface
 * @since      0.1.0
 */

namespace Sults\Writen\Interface;

use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Contracts\AssetLoaderInterface;

/**
 * Classe CategoryColorManager.
 *
 * Responsável por adicionar um campo de seleção de cor (Color Picker)
 * à taxonomia padrão de Categorias do WordPress.
 *
 * O objetivo é permitir que o administrador defina uma identidade visual para cada
 * editoria (ex: "Tecnologia" = Azul, "Marketing" = Laranja).
 * Essa cor é armazenada como `termmeta` e recuperada posteriormente para renderizar
 * "badges" coloridos no Painel de Tarefas (Workspace), facilitando a identificação visual.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface
 * @author     Sults
 * @since      0.1.0
 */
class CategoryColorManager implements HookableInterface {

	/** @var string Chave do metadado onde a cor é salva no banco. */
	private const META_KEY = '_sults_category_color';

	/** @var string Ação do Nonce para segurança no salvamento. */
	private const NONCE_ACTION = 'sults_save_category_color';

	/** @var string Nome do campo Nonce no formulário. */
	private const NONCE_FIELD = 'sults_category_color_nonce';

	/** @var string Cor padrão (Azul Sults) caso nenhuma seja definida. */
	public const DEFAULT_COLOR = '#206DF3';

	/**
	 * Gerenciador de assets para enfileirar scripts.
	 * @var AssetLoaderInterface
	 */
	private AssetLoaderInterface $asset_loader;

	/**
	 * Construtor.
	 *
	 * @param AssetLoaderInterface $asset_loader Serviço de carregamento de scripts.
	 */
	public function __construct( AssetLoaderInterface $asset_loader ) {
		$this->asset_loader = $asset_loader;
	}

	/**
	 * Registra os hooks necessários.
	 *
	 * Adiciona campos nos formulários de criação e edição de categorias,
	 * hooks de salvamento e scripts do color picker.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'category_add_form_fields', array( $this, 'add_color_field' ) );
		add_action( 'category_edit_form_fields', array( $this, 'edit_color_field' ) );
		add_action( 'created_category', array( $this, 'save_meta' ) );
		add_action( 'edited_category', array( $this, 'save_meta' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_color_picker' ) );
	}

	/**
	 * Carrega os scripts do Color Picker nativo do WP.
	 *
	 * Verifica se está na tela de edição de tags/categorias para não carregar assets desnecessariamente
	 * em outras páginas do admin.
	 *
	 * @param string $hook O sufixo da página atual do admin.
	 */
	public function enqueue_color_picker( string $hook ): void {
		if ( 'edit-tags.php' !== $hook && 'term.php' !== $hook ) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen && 'category' !== $screen->taxonomy ) {
			return;
		}

		// Carrega estilo nativo e script nativo do WP Color Picker.
		wp_enqueue_style( 'wp-color-picker' );
		
		// Script inline para inicializar o componente.
		$script = "jQuery(document).ready(function($){ $('.sults-color-field').wpColorPicker(); });";
		
		// Garante que a dependência 'wp-color-picker' esteja carregada via AssetLoader wrapper.
		$this->asset_loader->enqueue_script( 'wp-color-picker', '', array(), false, true );
		wp_add_inline_script( 'wp-color-picker', $script );
	}

	/**
	 * Renderiza o campo de cor na tela de "Adicionar Nova Categoria".
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function add_color_field(): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		?>
		<div class="form-field term-color-wrap">
			<label for="sults-category-color">Cor do Badge</label>
			<input type="text" name="sults_category_color" id="sults-category-color" value="<?php echo esc_attr( self::DEFAULT_COLOR ); ?>" class="sults-color-field" data-default-color="<?php echo esc_attr( self::DEFAULT_COLOR ); ?>">
			<p>Escolha a cor de fundo para o badge desta categoria no Workspace.</p>
		</div>
		<?php
	}

	/**
	 * Renderiza o campo de cor na tela de "Editar Categoria".
	 *
	 * Diferente da tela de adição, aqui precisamos usar estrutura de tabela (<tr>/<th>/<td>).
	 *
	 * @since 0.1.0
	 * @param \WP_Term $sults_term O objeto da categoria sendo editada.
	 * @return void
	 */
	public function edit_color_field( \WP_Term $sults_term ): void {
		$color = get_term_meta( $sults_term->term_id, self::META_KEY, true );
		$color = ( ! empty( $color ) ) ? $color : self::DEFAULT_COLOR;

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		?>
		<tr class="form-field term-color-wrap">
			<th scope="row"><label for="sults-category-color">Cor do Badge</label></th>
			<td>
				<input type="text" name="sults_category_color" id="sults-category-color" value="<?php echo esc_attr( $color ); ?>" class="sults-color-field" data-default-color="<?php echo esc_attr( self::DEFAULT_COLOR ); ?>">
				<p class="description">Escolha a cor de fundo para o badge desta categoria no Workspace.</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Salva o metadado da cor quando a categoria é criada ou atualizada.
	 *
	 * @since 0.1.0
	 * @param int $sults_term_id ID da categoria.
	 * @return void
	 */
	public function save_meta( int $sults_term_id ): void {
		// Verificação de segurança (Nonce).
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( isset( $_POST['sults_category_color'] ) ) {
			// Sanitiza como cor hexadecimal (ex: #ffffff).
			$color = sanitize_hex_color( wp_unslash( $_POST['sults_category_color'] ) );

			if ( $color ) {
				update_term_meta( $sults_term_id, self::META_KEY, $color );
			} else {
				delete_term_meta( $sults_term_id, self::META_KEY );
			}
		}
	}

	/**
	 * Recupera a cor de uma categoria (Helper Estático).
	 *
	 * @since 0.1.0
	 * @param int $sults_term_id ID da categoria.
	 * @return string A cor hexadecimal ou o padrão se não definida.
	 */
	public static function get_color( int $sults_term_id ): string {
		$color = get_term_meta( $sults_term_id, self::META_KEY, true );
		return $color ? $color : self::DEFAULT_COLOR;
	}
}
