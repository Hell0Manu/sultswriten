<?php
/**
 * Interface de Sanitização para JSP.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

/**
 * Interface JspHtmlSanitizerInterface.
 *
 * Específica para tratar HTML que será injetado em templates JSP.
 * Deve lidar com conflitos de sintaxe (como tags `<% %>`) e garantir encoding correto.
 */
interface JspHtmlSanitizerInterface {
	/**
	 * Prepara o HTML para inclusão em arquivo JSP.
	 *
	 * @since 0.1.0
	 * @param string $html O HTML processado.
	 * @return string O HTML seguro para JSP.
	 */
	public function sanitize( string $html ): string;
}