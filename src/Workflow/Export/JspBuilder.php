<?php
namespace Sults\Writen\Workflow\Export;

use Sults\Writen\Contracts\JspBuilderInterface;

class JspBuilder implements JspBuilderInterface {

	public function build( string $html_content, string $sults_page_title, array $meta_data, string $active_group = '' ): string {

		$seo_title = isset( $meta_data['title'] ) ? $meta_data['title'] : $sults_page_title;
		$seo_desc  = isset( $meta_data['description'] ) ? $meta_data['description'] : '';

		$safe_seo_title    = htmlspecialchars( $seo_title, ENT_QUOTES, 'UTF-8' );
		$safe_seo_desc     = htmlspecialchars( $seo_desc, ENT_QUOTES, 'UTF-8' );
		$safe_page_title   = htmlspecialchars( $sults_page_title, ENT_QUOTES, 'UTF-8' );
		$safe_sidebar_name = htmlspecialchars( $active_group, ENT_QUOTES, 'UTF-8' );

		$title_parts      = explode( ':', $sults_page_title );
		$active_item_raw  = $title_parts[0];
		$safe_active_item = htmlspecialchars( trim( $active_item_raw ), ENT_QUOTES, 'UTF-8' );

		// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
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
