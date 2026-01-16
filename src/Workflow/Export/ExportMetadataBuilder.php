<?php
namespace Sults\Writen\Workflow\Export;

use WP_Post;
use Sults\Writen\Utils\PathHelper;

class ExportMetadataBuilder {

	public function build_info_file( WP_Post $sults_post ): string {

		$raw_path = PathHelper::get_relative_path( $sults_post->ID );

		$sults_path = rtrim( $raw_path, '/' );

		$domain_prod = 'https://www.sults.com.br';
		$base_prod   = '/produtos';
		$base_jsp    = '/sults/pages/produtos';

		// Input: /checklist/categoria/post.
		// Output desejado: /checklist/artigos/categoria/post.

		$sults_parts = explode( '/', ltrim( $sults_path, '/' ) );
		$module      = $sults_parts[0] ?? '';

		$final_path = $sults_path;

		if ( 'checklist' === $module && count( $sults_parts ) > 1 ) {
			$suffix     = substr( $sults_path, strlen( '/' . $module ) );
			$final_path = '/' . $module . '/artigos' . $suffix;
		}

		$rewrite_from = '^' . $sults_path . '$';
		$rewrite_to   = $base_jsp . $final_path . '.jsp';

		$sitemap_loc = $domain_prod . $final_path;
		$last_mod    = get_the_modified_date( 'Y-m-d', $sults_post );

		$content = '';

		$content .= "=== URLREWRITE ===\n";
		$content .= "<rule>\n";
		$content .= "    <from>{$rewrite_from}</from>\n";
		$content .= "    <to>{$rewrite_to}</to>\n";
		$content .= "</rule>\n\n";

		$content .= "=== SITEMAP ===\n";
		$content .= "<url>\n";
		$content .= "    <loc>{$sitemap_loc}</loc>\n";
		$content .= "    <lastmod>{$last_mod}</lastmod>\n";
		$content .= '</url>';

		return $content;
	}
}
