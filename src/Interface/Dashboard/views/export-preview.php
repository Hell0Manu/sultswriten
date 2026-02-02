<?php
/**
 * View: Tela de Prévia de Exportação (Code Diff).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface\Dashboard\Views
 * @since      0.1.0
 *
 * @var \WP_Post $sults_post  O objeto do post sendo visualizado.
 * @var string   $back_url    URL para retornar à listagem de exportação.
 * @var string   $html_raw    O HTML original do WordPress (antes do processamento).
 * @var string   $html_clean  O HTML higienizado (após HtmlCleaner e Transformers).
 * @var string   $jsp_content O conteúdo final encapsulado na estrutura JSP.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Gera a URL para o download direto do ZIP (Endpoint POST).
$sultswriten_download_url = add_query_arg(
	array(
		'action'   => 'sults_export_download', 
		'post_id'  => $sults_post->ID,
		'_wpnonce' => wp_create_nonce( 'sults_export_' . $sults_post->ID ),
	),
	admin_url( 'admin-post.php' ) 
);
?>

<div class="wrap sults-preview-wrap">
	
	<h1><?php echo esc_html( get_the_title( $sults_post ) ); ?></h1>
	<div class="sults-actions-bar" style="margin-bottom: 20px;">
		<a href="<?php echo esc_url( $back_url ); ?>" class="button">Voltar à lista</a>
		
		<a href="<?php echo esc_url( $sultswriten_download_url ); ?>" class="button button-primary button-large">
			<span class="dashicons dashicons-download" style="margin-top:4px;"></span> Baixar ZIP
		</a>
	</div>

	<nav class="nav-tab-wrapper wp-clearfix" style="margin-top: 20px;">   
		<a href="#" class="nav-tab nav-tab-active sults-view-toggle" data-mode="conversion">
			<span class="dashicons dashicons-media-code" style="margin-right:5px; margin-top:3px;"></span>
			HTML Limpo &rarr; JSP
		</a>
		<a href="#" class="nav-tab sults-view-toggle" data-mode="cleaning">
			<span class="dashicons dashicons-filter" style="margin-right:5px; margin-top:3px;"></span>
			HTML Puro &rarr; Limpo
		</a>
	</nav>

	<div class="sults-preview-flex-container">
		
		<div class="sults-code-pane">
			<div class="sults-editor-wrapper">
				<button type="button" class="sults-copy-btn" data-target="left">
					<span class="dashicons dashicons-clipboard"></span> 
					<span class="btn-text">Copiar</span>
				</button>
				<textarea id="code-left" name="code-left"></textarea>
			</div>
		</div>

		<div class="sults-code-pane">
			<div class="sults-editor-wrapper">
				<button type="button" class="sults-copy-btn" data-target="right">
					<span class="dashicons dashicons-clipboard"></span> 
					<span class="btn-text">Copiar</span>
				</button>
				<textarea id="code-right" name="code-right"></textarea>
			</div>
		</div>
	</div>

	<input type="hidden" id="data-raw" value="<?php echo esc_attr( $html_raw ); ?>">
	<input type="hidden" id="data-clean" value="<?php echo esc_attr( $html_clean ); ?>">
	<input type="hidden" id="data-jsp" value="<?php echo esc_attr( $jsp_content ); ?>">
</div>