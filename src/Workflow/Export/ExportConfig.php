<?php
/**
 * Configurações de Exportação e Sanitização.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Export;

/**
 * Classe ExportConfig.
 *
 * Centraliza constantes e regras utilizadas durante a transformação do HTML.
 *
 * É utilizada principalmente pelos sanitizadores (`HtmlExtractor` ou `JspHtmlSanitizer`)
 * para filtrar o conteúdo, garantindo que o HTML exportado para o sistema Java
 * seja limpo e contenha apenas as classes CSS estritamente necessárias.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export
 * @author     Sults
 * @since      0.1.0
 */
class ExportConfig {

	/**
	 * Lista de classes CSS permitidas na exportação (Allowlist).
	 *
	 * Qualquer classe encontrada no HTML que NÃO estiver nesta lista será removida
	 * durante o processo de sanitização.
	 *
	 * Inclui:
	 * - Classes estruturais do Gutenberg (wp-block-columns, etc).
	 * - Classes de alinhamento (aligncenter, etc).
	 * - Classes utilitárias de texto.
	 * - Classes personalizadas do plugin (dica-sults).
	 *
	 * @var array<string>
	 */
	public const ALLOWED_CLASSES = array(
		// Estrutura Gutenberg
		'wp-block-columns',
		'wp-block-column',
		'wp-block-image',
		'wp-block-table',
		'wp-block-separator',
		'wp-block-quote',
		
		// Alinhamentos
		'aligncenter',
		'alignleft',
		'alignright',
		'is-style-default',
		'has-text-align-center',
		
		// Personalizados Sults
		'dica-sults',
	);
}