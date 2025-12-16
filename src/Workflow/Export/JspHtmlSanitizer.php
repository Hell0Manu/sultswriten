<?php
	/**
	 * Prepara o HTML para ser inserido com segurança em um atributo de tag JSP.
	 *
	 * Realiza duas operações:
	 * 1. Normaliza atributos HTML para usar aspas simples.
	 * 2. Escapa aspas duplas no conteúdo para evitar quebra do atributo value="" do JSP.
	 *
	 * @param string $html O HTML cru.
	 * @return string O HTML sanitizado.
	 */
namespace Sults\Writen\Workflow\Export;

use Sults\Writen\Contracts\JspHtmlSanitizerInterface;

class JspHtmlSanitizer implements JspHtmlSanitizerInterface {

	public function sanitize( string $html ): string {
		// Normaliza atributos para aspas simples: class="foo" -> class='foo'.
		$html_attributes_fixed = preg_replace( '/( [a-zA-Z0-9_\-]+)=["\']([^"\']*)["\']/', '$1=\'$2\'', $html );
		return str_replace( '"', '&#34;', $html_attributes_fixed );
	}
}
