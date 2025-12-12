<?php
/**
 * View de Prévia de Exportação.
 *
 * @var \WP_Post $post
 * @var string $back_url
 * @var string $html_raw
 * @var string $html_clean
 * @var string $jsp_content
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap sults-preview-wrap">
	
	<h1><?php echo esc_html( get_the_title( $post ) ); ?></h1>
	<a href="<?php echo esc_url( $back_url ); ?>" class="page-title-action">Voltar à lista</a>  

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