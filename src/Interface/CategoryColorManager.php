<?php
namespace Sults\Writen\Interface;

use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Contracts\AssetLoaderInterface;

class CategoryColorManager implements HookableInterface {

	private const META_KEY = '_sults_category_color';
	
    public const DEFAULT_COLOR = '#206DF3';
	
	private AssetLoaderInterface $asset_loader;

	public function __construct( AssetLoaderInterface $asset_loader ) {
		$this->asset_loader = $asset_loader;
	}

	public function register(): void {
		add_action( 'category_add_form_fields', array( $this, 'add_color_field' ) );
		add_action( 'category_edit_form_fields', array( $this, 'edit_color_field' ) );
		add_action( 'created_category', array( $this, 'save_meta' ) );
		add_action( 'edited_category', array( $this, 'save_meta' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_color_picker' ) );
	}

	public function enqueue_color_picker( string $hook ): void {
		if ( 'edit-tags.php' !== $hook && 'term.php' !== $hook ) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen && 'category' !== $screen->taxonomy ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		$script = "jQuery(document).ready(function($){ $('.sults-color-field').wpColorPicker(); });";
		$this->asset_loader->enqueue_script( 'wp-color-picker', '', array(), false, true ); 
		wp_add_inline_script( 'wp-color-picker', $script );
	}

	public function add_color_field(): void {
		?>
		<div class="form-field term-color-wrap">
			<label for="sults-category-color">Cor do Badge</label>
			<input type="text" name="sults_category_color" id="sults-category-color" value="<?php echo esc_attr( self::DEFAULT_COLOR ); ?>" class="sults-color-field" data-default-color="<?php echo esc_attr( self::DEFAULT_COLOR ); ?>">
			<p>Escolha a cor de fundo para o badge desta categoria no Workspace.</p>
		</div>
		<?php
	}

	public function edit_color_field( \WP_Term $term ): void {
		$color = get_term_meta( $term->term_id, self::META_KEY, true );

		$color = ( ! empty( $color ) ) ? $color : self::DEFAULT_COLOR;
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

	public function save_meta( int $term_id ): void {
		if ( isset( $_POST['sults_category_color'] ) ) {
			$color = sanitize_hex_color( $_POST['sults_category_color'] );
			if ( $color ) {
				update_term_meta( $term_id, self::META_KEY, $color );
			} else {
				delete_term_meta( $term_id, self::META_KEY );
			}
		}
	}
	
	public static function get_color( int $term_id ): string {
		$color = get_term_meta( $term_id, self::META_KEY, true );
		return $color ? $color : self::DEFAULT_COLOR;
	}
}