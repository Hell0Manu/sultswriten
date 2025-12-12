<?php
namespace Sults\Writen\Contracts;

interface JspBuilderInterface {
	/**
	 * Constrói o conteúdo final do arquivo JSP.
	 *
	 * @param string $html_content O HTML já higienizado e transformado.
	 * @param string $page_title O título H1 da página.
	 * @param array  $meta_data Dados de SEO (title, description).
	 * @return string O código JSP completo.
	 */
	public function build( string $html_content, string $page_title, array $meta_data ): string;
}