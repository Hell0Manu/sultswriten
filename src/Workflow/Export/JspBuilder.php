<?php
/**
 * Construtor de Arquivos JSP (JavaServer Pages).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Export;

use Sults\Writen\Contracts\JspBuilderInterface;

/**
 * Classe JspBuilder.
 *
 * Responsável por montar o arquivo final `.jsp` que será interpretado pelo servidor Tomcat.
 *
 * Esta classe atua como um "Template Engine", inserindo o conteúdo HTML processado
 * dentro da estrutura de layout padrão do sistema Sults (Header, Sidebar, Footer),
 * utilizando as tags de inclusão nativas do JSP (`<jsp:include>`).
 *
 * @see JspBuilderInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export
 * @author     Sults
 * @since      0.1.0
 */
class JspBuilder implements JspBuilderInterface {

	/**
	 * Constrói o código fonte do arquivo JSP.
	 *
	 * Combina metadados de SEO, título da página e o conteúdo HTML higienizado
	 * em uma string formatada.
	 *
	 * Lógica do Menu Lateral (Sidebar):
	 * O sistema legado utiliza os parâmetros `active_group` (Categoria) e `active_item` (Post)
	 * para expandir o menu e destacar a página atual.
	 * O `active_item` é derivado do slug, substituindo hifens por espaços.
	 *
	 * @since 0.1.0
	 *
	 * @param string $html_content     O corpo do artigo (HTML limpo).
	 * @param string $sults_page_title O título H1 da página.
	 * @param string $post_slug        O slug do post (usado para calcular o item ativo).
	 * @param array  $meta_data        Dados de SEO (title, description).
	 * @param string $active_group     Nome da categoria pai para abrir o menu correto.
	 * @return string O código fonte completo do JSP.
	 */
	public function build( string $html_content, string $sults_page_title, string $post_slug, array $meta_data, string $active_group = '' ): string {
		
		// Fallback para SEO: Se não houver dados do Yoast/RankMath, usa o título do WP.
		$seo_title = isset( $meta_data['title'] ) ? $meta_data['title'] : $sults_page_title;
		$seo_desc  = isset( $meta_data['description'] ) ? $meta_data['description'] : '';

		// Escaping para garantir que aspas no título não quebrem a tag JSP.
		$safe_seo_title    = htmlspecialchars( $seo_title, ENT_QUOTES, 'UTF-8' );
		$safe_seo_desc     = htmlspecialchars( $seo_desc, ENT_QUOTES, 'UTF-8' );
		$safe_page_title   = htmlspecialchars( $sults_page_title, ENT_QUOTES, 'UTF-8' );
		$safe_sidebar_name = htmlspecialchars( $active_group, ENT_QUOTES, 'UTF-8' );

		// Regra de Negócio Legada:
		// O sistema espera que o nome do item no menu seja o slug com espaços em vez de hifens.
		// Ex: "como-usar" vira "como usar".
		$processed_slug  = str_replace( '-', ' ', $post_slug );
		$active_item_raw = $processed_slug;

		// Se não tiver grupo (categoria), assume que é uma página de Visão Geral.
		if ( empty( $active_group ) ) {
			$active_item_raw = 'Visão Geral';
		}
		$safe_active_item = htmlspecialchars( trim( $active_item_raw ), ENT_QUOTES, 'UTF-8' );

		// Montagem do Template JSP usando sintaxe HEREDOC.
		// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Falso positivo, estamos gerando string, não enfileirando no WP.
		// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript
		return <<<JSP
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <jsp:include page="/sults/components/default/include_meta.jsp">
        <jsp:param name="meta_title" value="{$safe_seo_title}"/>
        <jsp:param name="meta_description" value="{$safe_seo_desc}"/>
    </jsp:include>
    <link rel="preload" href="/sults/assets/js/script_lazyframe.js" as="script">
    <script src="/sults/assets/js/script_lazyframe.js"></script>
    <link rel="preload" href="/sults/assets/css/style_lazyframe.css" as="style">
    <link rel="stylesheet" href="/sults/assets/css/style_lazyframe.css">
</head>
<body>
    <jsp:include page="/sults/components/elements/include_elements_menu.jsp"/>

    <main>
        <jsp:include page="/sults/components/content/include_content_page_checklist.jsp">
            <jsp:param name="page_title" value="{$safe_page_title}"/>
            <jsp:param name="active_group" value="{$safe_sidebar_name}"/>
            <jsp:param name="active_item" value="{$safe_active_item}"/>
            <jsp:param name="description1" value="{$html_content}"/>
        </jsp:include>
    </main>

    <%@ include file="/sults/components/section/include_section_clientes.jsp" %>
    <%@ include file="/sults/components/section/include_section_call_to_action.jsp" %>
    <%@ include file="/sults/components/elements/include_elements_footer.jsp" %>
</body>
</html>
JSP;
		// phpcs:enable
	}
}