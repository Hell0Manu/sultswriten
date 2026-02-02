<?php
/**
 * Limpeza de Interface do AIOSEO.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Integrations\AIOSEO
 * @since      0.1.0
 */

namespace Sults\Writen\Integrations\AIOSEO;

use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Workflow\Permissions\RoleDefinitions;

/**
 * Classe AIOSEOCleaner.
 *
 * Responsável por simplificar a interface do plugin All in One SEO (AIOSEO)
 * para usuários operacionais (Redatores e Corretores).
 *
 * O objetivo é reduzir a carga cognitiva e impedir alterações em configurações
 * técnicas (Schema, Social, Redirecionamentos), mantendo o foco apenas
 * no Título SEO e Meta Descrição.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Integrations\AIOSEO
 * @author     Sults
 * @since      0.1.0
 */
class AIOSEOCleaner implements HookableInterface {

	/**
	 * Provedor de dados do usuário.
	 * @var WPUserProviderInterface
	 */
	private WPUserProviderInterface $user_provider;

	/**
	 * Lista de papéis que devem ter a interface simplificada.
	 *
	 * @var array<string>
	 */
	private array $restricted_roles = array(
		RoleDefinitions::REDATOR,
		RoleDefinitions::CORRETOR,
	);

	/**
	 * Construtor.
	 *
	 * @param WPUserProviderInterface $user_provider Serviço de usuário.
	 */
	public function __construct( WPUserProviderInterface $user_provider ) {
		$this->user_provider = $user_provider;
	}

	/**
	 * Registra os hooks de limpeza.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		// Remove metaboxes do backend (lado do servidor).
		add_action( 'add_meta_boxes', array( $this, 'remove_unwanted_metaboxes' ), 999 );
		
		// Oculta abas e elementos via CSS/JS (lado do cliente).
		add_action( 'admin_head', array( $this, 'hide_tabs_and_elements' ) );
	}

	/**
	 * Verifica se o usuário atual pertence a um dos grupos restritos.
	 *
	 * @return bool True se for Redator ou Corretor.
	 */
	public function is_restricted_user(): bool {
		$user_roles = $this->user_provider->get_current_user_roles();

		// Verifica intersecção entre os roles do usuário e os restritos.
		$intersection = array_intersect( $this->restricted_roles, $user_roles );

		return ! empty( $intersection );
	}

	/**
	 * Remove metaboxes que não são essenciais para a escrita.
	 *
	 * Remove especificamente o "Writing Assistant" do AIOSEO, que pode ser intrusivo.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function remove_unwanted_metaboxes(): void {
		if ( ! $this->is_restricted_user() ) {
			return;
		}

		remove_meta_box( 'aioseo-writing-assistant-metabox', 'post', 'normal' );
	}

	/**
	 * Injeta estilos e scripts para ocultar partes da interface React do AIOSEO.
	 *
	 * Como o AIOSEO renderiza muitas coisas via React (sem hooks PHP tradicionais para remover abas),
	 * utilizamos CSS (`display: none`) e um script JS intervalado para remover abas avançadas
	 * (Social, Schema, Advanced) assim que elas aparecem no DOM.
	 *
	 * @since 0.1.0
	 * @return void
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
			/* Oculta linhas específicas de configuração de palavras-chave e conteúdo estruturante */
			#aioseo-settings .snippet-focus-keyphrase-row,    
			#aioseo-settings .snippet-additional-keyphrases-row, 
			#aioseo-settings .cornerstone-content-row {    
				display: none !important;
			}
		</style>

		<script type="text/javascript">
			jQuery(document).ready(function($) {
				// Intervalo para verificar a renderização do React do AIOSEO.
				var cleanAIOSEO = setInterval(function() {
					var $box = $('#aioseo-settings');
					
					if ($box.length > 0) {
						$box.find('.var-tab').each(function() {
							var $tab = $(this);
							var labelText = $tab.find('.tab-label').text().trim();

							// Lista de abas proibidas para redatores.
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

				// Para a verificação após 10 segundos para economizar recursos.
				setTimeout(function() { clearInterval(cleanAIOSEO); }, 10000);
			});
		</script>
		<?php
	}
}