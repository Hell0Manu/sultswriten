<?php
/**
 * Gerencia as modificações de interface para o plugin AIOSEO.
 *
 * Oculta funcionalidades avançadas do AIOSEO para redatores e corretores.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Integrations\AIOSEO
 * @since      0.1.0
 */

namespace Sults\Writen\Integrations\AIOSEO;

use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\HookableInterface;

class AIOSEOCleaner implements HookableInterface {

	private WPUserProviderInterface $user_provider;

	/**
	 * Roles que terão acesso restrito ao AIOSEO.
	 *
	 * @var array
	 */
	private array $restricted_roles = array( 'contributor', 'author', 'redator', 'corretor' );

	public function __construct( WPUserProviderInterface $user_provider ) {
		$this->user_provider = $user_provider;
	}

	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'remove_unwanted_metaboxes' ), 999 );
		add_action( 'admin_head', array( $this, 'hide_tabs_and_elements' ) );
	}

	/**
	 * Verifica se o usuário atual tem acesso restrito.
	 */
	public function is_restricted_user(): bool {
		$user_roles = $this->user_provider->get_current_user_roles();

		$intersection = array_intersect( $this->restricted_roles, $user_roles );

		return ! empty( $intersection );
	}

	/**
	 * Remove o metabox "Writing Assistant" e outros indesejados.
	 */
	public function remove_unwanted_metaboxes(): void {
		if ( ! $this->is_restricted_user() ) {
			return;
		}

		remove_meta_box( 'aioseo-writing-assistant-metabox', 'post', 'normal' );
	}

	/**
	 * Injeta CSS e JS para ocultar elementos que não possuem hooks do PHP.
	 */
	public function hide_tabs_and_elements(): void {
		if ( ! $this->is_restricted_user() ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->base ) {
			return;
		}
		?>
		<style type="text/css">
			#aioseo-settings .snippet-focus-keyphrase-row,    
			#aioseo-settings .snippet-additional-keyphrases-row, 
			#aioseo-settings .cornerstone-content-row {    
				display: none !important;
			}
		</style>

		<script type="text/javascript">
			jQuery(document).ready(function($) {
				var cleanAIOSEO = setInterval(function() {
					var $box = $('#aioseo-settings');
					
					if ($box.length > 0) {
						$box.find('.var-tab').each(function() {
							var $tab = $(this);
							var labelText = $tab.find('.tab-label').text().trim();

							var tabsToHide = [
								'Social', 'Esquema', 'Schema', 'AI Content', 
								'Avançado', 'Advanced', 'Redirecionamentos', 'Redirects'
							];

							if (tabsToHide.includes(labelText)) {
								$tab.hide();
							}
						});
					}
				}, 10); 

				setTimeout(function() { clearInterval(cleanAIOSEO); }, 10000);
			});
		</script>
		<?php
	}
}
