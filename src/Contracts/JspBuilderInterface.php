<?php
/**
 * Interface do Construtor JSP.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

/**
 * Interface JspBuilderInterface.
 *
 * Define o contrato para montar o arquivo `.jsp` final, injetando o cabeçalho,
 * rodapé, metadados e o conteúdo HTML processado no template JSP padrão.
 */
interface JspBuilderInterface {
    /**
	 * Constrói o conteúdo final do arquivo JSP.
	 *
	 * @since 0.1.0
	 * @param string $html_content     HTML do corpo da página.
	 * @param string $sults_page_title Título H1.
	 * @param string $post_slug        Slug para identificar o item ativo no menu.
	 * @param array  $meta_data        Dados de SEO (title, description).
	 * @param string $active_group     Identificador do grupo ativo na sidebar.
	 * @return string O código fonte completo do arquivo JSP.
	 */
    public function build( string $html_content, string $sults_page_title, string $post_slug, array $meta_data, string $active_group = '' ): string;
}