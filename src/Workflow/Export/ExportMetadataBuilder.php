<?php
namespace Sults\Writen\Workflow\Export;

use WP_Post;

class ExportMetadataBuilder {

    public function build_info_file( WP_Post $post ): string {
        $slug      = $post->post_name;
        $last_mod  = get_the_modified_date( 'Y-m-d', $post ); 
        
        $rewrite  = "<rule>\n";
        $rewrite .= "    <from>^/checklist/{$slug}$</from>\n";
        $rewrite .= "    <to>/sults/pages/produtos/checklist/artigos/{$slug}.jsp</to>\n";
        $rewrite .= "</rule>";

        $sitemap  = "<url>\n";
        $sitemap .= "    <loc>https://www.sults.com.br/produtos/checklist/artigos/{$slug}</loc>\n";
        $sitemap .= "    <lastmod>{$last_mod}</lastmod>\n";
        $sitemap .= "</url>";

        return "=== URLREWRITE ===\n" . 
               $rewrite . 
               "\n\n" . 
               "=== SITEMAP ===\n" . 
               $sitemap;
    }
}