<?php
/**
 * Responsável por orquestrar todo o processo de geração de conteúdo para exportação.
 * Combina: Extração HTML -> Processamento de Assets -> Geração de JSP.
 */

namespace Sults\Writen\Workflow\Export;

use WP_Post;
use Sults\Writen\Contracts\HtmlExtractorInterface;
use Sults\Writen\Contracts\JspBuilderInterface;
use Sults\Writen\Contracts\SeoDataProviderInterface;
use Sults\Writen\Workflow\Export\ExportAssetsManager;
use Sults\Writen\Contracts\JspHtmlSanitizerInterface;
use Sults\Writen\Workflow\Export\ExportMetadataBuilder;
use Sults\Writen\Utils\PathHelper;

class ExportProcessor {

	private HtmlExtractorInterface $extractor;
	private ExportAssetsManager $assets_manager;
	private SeoDataProviderInterface $seo_provider;
	private JspBuilderInterface $jsp_builder;
	private JspHtmlSanitizerInterface $sanitizer;
	private ExportMetadataBuilder $metadata_builder;

	public function __construct(
		HtmlExtractorInterface $extractor,
		ExportAssetsManager $assets_manager,
		SeoDataProviderInterface $seo_provider,
		JspBuilderInterface $jsp_builder,
		JspHtmlSanitizerInterface $sanitizer,
		ExportMetadataBuilder $metadata_builder
	) {
		$this->extractor      = $extractor;
		$this->assets_manager = $assets_manager;
		$this->seo_provider   = $seo_provider;
		$this->jsp_builder    = $jsp_builder;
		$this->sanitizer      = $sanitizer;
		$this->metadata_builder = $metadata_builder;
	}

	public function execute( int $post_id, string $zip_folder_prefix ): array {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			throw new \InvalidArgumentException( 'Post não encontrado.' );
		}

		$terms = get_the_terms( $post_id, 'sidebar' );
    	$sidebar = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';

		$html_raw   = $post->post_content;
		$html_clean = $this->extractor->extract( $post );

		/* @var ExportPayload $assets_payload */
		$assets_payload = $this->assets_manager->process( $html_clean, $zip_folder_prefix );

		$final_html_for_jsp = $assets_payload->html_content;
		$files_to_zip       = $assets_payload->files_to_zip;

		$safe_html_for_jsp = $this->sanitizer->sanitize( $final_html_for_jsp );
		$seo_data   = $this->seo_provider->get_seo_data( $post_id );
		$page_title = get_the_title( $post );
		
		$active_group_name = '';

		$cats = get_the_category( $post_id );
        
        if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
            foreach ( $cats as $cat ) {
                if ( $cat->parent > 0 ) {
                    $active_group_name = $cat->name; 
                    break; 
                }
            }
        }

		$jsp_content = $this->jsp_builder->build( $safe_html_for_jsp, $page_title, $seo_data, $active_group_name);

		$info_content = $this->metadata_builder->build_info_file( $post );
		$jsp_folder_path = $this->calculate_jsp_folder_path( $post_id );
		return array(
			'jsp_content' => $jsp_content,
			'info_content' => $info_content,
			'files_map'   => $files_to_zip,
			'html_clean'  => $html_clean,
			'html_raw'    => $html_raw,
			'jsp_folder_path' => $jsp_folder_path,
		);
	}

	/**
     * Transforma: /checklist/faq/solucao/implantacao-de-software (Slug do WP)
     * Em: sults/pages/produtos/checklist/artigos/faq/solucao (Pasta do ZIP)
     */
    private function calculate_jsp_folder_path( int $post_id ): string {
        $relative_path = PathHelper::get_relative_path( $post_id );
        
        $parts = explode( '/', trim( $relative_path, '/' ) );
        
        if ( count( $parts ) > 0 ) {
            array_pop( $parts ); 
        }

        if ( ! empty( $parts ) && $parts[0] === 'checklist' ) {
            if ( ! isset( $parts[1] ) || $parts[1] !== 'artigos' ) {
                array_splice( $parts, 1, 0, 'artigos' );
            }
        }
        array_unshift( $parts, 'sults', 'pages', 'produtos' );

        return implode( '/', $parts );
    }
}