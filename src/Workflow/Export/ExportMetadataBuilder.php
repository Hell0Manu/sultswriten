<?php
namespace Sults\Writen\Workflow\Export;

use WP_Post;

class ExportMetadataBuilder {

    public function build_info_file( WP_Post $post, string $sidebar = '' ): string {
        $slug      = $post->post_name;
        $last_mod  = get_the_modified_date( 'Y-m-d', $post ); 
        
        $safe_sidebar_name = sanitize_title( $sidebar );

        $rewrite  = "<rule>\n";
        
        if ( ! empty( $safe_sidebar_name ) ) {
             $rewrite .= "    <from>^/checklist/{$safe_sidebar_name}/{$slug}$</from>\n";
             $rewrite .= "    <to>/sults/pages/produtos/checklist/artigos/{$safe_sidebar_name}/{$slug}.jsp</to>\n";
        } else {
             $rewrite .= "    <from>^/checklist/{$slug}$</from>\n";
             $rewrite .= "    <to>/sults/pages/produtos/checklist/artigos/{$slug}.jsp</to>\n";
        }
        $rewrite .= "</rule>";

        $sitemap  = "<url>\n";
        if ( ! empty( $safe_sidebar_name ) ) {
            $sitemap .= "    <loc>https://www.sults.com.br/produtos/checklist/artigos/{$safe_sidebar_name}/{$slug}</loc>\n";
        } else {
            $sitemap .= "    <loc>https://www.sults.com.br/produtos/checklist/artigos/{$slug}</loc>\n";
        }
        $sitemap .= "    <lastmod>{$last_mod}</lastmod>\n";
        $sitemap .= "</url>";

        return "=== URLREWRITE ===\n" . 
               $rewrite . 
               "\n\n" . 
               "=== SITEMAP ===\n" . 
               $sitemap;
    }
}