<?php
namespace Sults\Writen\Contracts;

interface JspBuilderInterface {
    /**
     * Constrói o conteúdo final do arquivo JSP.
     *
     * @param string $html_content O HTML já higienizado e transformado.
     * @param string $sults_page_title O título H1 da página.
     * @param string $post_slug O slug (URL amigável) do post para o active_item.
     * @param array  $meta_data Dados de SEO (title, description).
     * @param string $active_group O nome da sidebar ativa (se houver).
     * @return string O código JSP completo.
     */
    public function build( string $html_content, string $sults_page_title, string $post_slug, array $meta_data, string $active_group = '' ): string;
}