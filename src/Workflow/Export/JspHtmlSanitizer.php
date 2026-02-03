<?php
/**
 * Sanitizador de HTML para Contexto JSP.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Export;

use Sults\Writen\Contracts\JspHtmlSanitizerInterface;

/**
 * Classe JspHtmlSanitizer.
 *
 * Responsável por preparar strings HTML para serem injetadas com segurança
 * dentro de atributos de tags JSP.
 *
 * Cenário do Problema:
 * O sistema legado recebe o conteúdo via parâmetro na tag:
 * `<jsp:param name="description1" value="CONTEUDO_HTML" />`
 *
 * Se o `CONTEUDO_HTML` contiver aspas duplas (ex: `<div class="box">`),
 * a string do atributo `value` será fechada prematuramente, gerando erro de compilação
 * no servidor Tomcat ("Attribute value is not quoted properly").
 *
 * Solução Implementada:
 * 1. Converte atributos HTML internos para aspas simples (ex: `class='box'`).
 * 2. Escapa quaisquer aspas duplas restantes (no texto) para a entidade `&#34;`.
 *
 * @see JspHtmlSanitizerInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export
 * @author     Sults
 * @since      0.1.0
 */
class JspHtmlSanitizer implements JspHtmlSanitizerInterface {

	/**
	 * Sanitiza o HTML para inclusão em atributo JSP.
	 *
	 * @since 0.1.0
	 *
	 * @param string $html O HTML cru (vindo do Extractor).
	 * @return string O HTML sanitizado (seguro para value="...").
	 */
	public function sanitize( string $html ): string {
		// Remove espaços não separáveis (NBSP) e placeholders literais.
		// Converte &nbsp; (entidade), \xc2\xa0 (caractere unicode) e [NBSP] (se existir literal) para espaço simples.
		$html = str_replace( array( '&nbsp;', "\xc2\xa0", '[NBSP]' ), ' ', $html );

		// Remove tags <strong> vazias ou que contêm apenas espaços em branco.
		// Regex: <strong ...>(qualquer espaço)</strong>
		$html = preg_replace( '/<strong\b[^>]*>(\s)*<\/strong>/i', '', $html );

		// Passo 1: Normaliza atributos das tags HTML para usar aspas simples.
		// Regex: Procura por [espaço][chave]=["valor"] e troca por [espaço][chave]='valor'.
		// Exemplo: <img src="foto.jpg"> vira <img src='foto.jpg'>.
		$html_attributes_fixed = preg_replace( '/( [a-zA-Z0-9_\-]+)=["\']([^"\']*)["\']/', '$1=\'$2\'', $html );

		// Passo 2: Escapa aspas duplas restantes no conteúdo textual.
		// Exemplo: O texto 'Ele disse "Olá"' vira 'Ele disse &#34;Olá&#34;'.
		// Isso garante que o parser do JSP não confunda com o fechamento do atributo value.
		return str_replace( '"', '&#34;', $html_attributes_fixed ?? '' );
	}
}