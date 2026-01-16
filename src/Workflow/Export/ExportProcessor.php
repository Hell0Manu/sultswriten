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
		$this->extractor        = $extractor;
		$this->assets_manager   = $assets_manager;
		$this->seo_provider     = $seo_provider;
		$this->jsp_builder      = $jsp_builder;
		$this->sanitizer        = $sanitizer;
		$this->metadata_builder = $metadata_builder;
	}

	public function execute( int $sults_post_id, string $zip_folder_prefix ): array {
		$sults_post = get_post( $sults_post_id );
		if ( ! $sults_post instanceof WP_Post ) {
			throw new \InvalidArgumentException( 'Post não encontrado.' );
		}

		$sults_terms = get_the_terms( $sults_post_id, 'sidebar' );
		$sidebar     = ( ! empty( $sults_terms ) && ! is_wp_error( $sults_terms ) ) ? $sults_terms[0]->name : '';

		$html_raw   = $sults_post->post_content;
		$html_clean = $this->extractor->extract( $sults_post );

		/* @var ExportPayload $assets_payload */
		$assets_payload = $this->assets_manager->process( $html_clean, $zip_folder_prefix );

		$final_html_for_jsp = $assets_payload->html_content;
		$files_to_zip       = $assets_payload->files_to_zip;

		$safe_html_for_jsp = $this->sanitizer->sanitize( $final_html_for_jsp );
		$seo_data          = $this->seo_provider->get_seo_data( $sults_post_id );
		$sults_page_title  = get_the_title( $sults_post );

		$active_group_name = '';

		$sults_cats = get_the_category( $sults_post_id );

		if ( ! empty( $sults_cats ) && ! is_wp_error( $sults_cats ) ) {
			foreach ( $sults_cats as $sults_cat ) {
				if ( $sults_cat->parent > 0 ) {
					$active_group_name = $sults_cat->name;
					break;
				}
			}
		}

		$jsp_content = $this->jsp_builder->build( $safe_html_for_jsp, $sults_page_title, $seo_data, $active_group_name );

		$info_content    = $this->metadata_builder->build_info_file( $sults_post );
		$jsp_folder_path = $this->calculate_jsp_folder_path( $sults_post_id );
		return array(
			'jsp_content'     => $jsp_content,
			'info_content'    => $info_content,
			'files_map'       => $files_to_zip,
			'html_clean'      => $html_clean,
			'html_raw'        => $html_raw,
			'jsp_folder_path' => $jsp_folder_path,
		);
	}

	/**
	 * Transforma: /checklist/faq/solucao/implantacao-de-software (Slug do WP)
	 * Em: sults/pages/produtos/checklist/artigos/faq/solucao (Pasta do ZIP)
	 */
	private function calculate_jsp_folder_path( int $sults_post_id ): string {
		$relative_path = PathHelper::get_relative_path( $sults_post_id );

		$sults_parts = explode( '/', trim( $relative_path, '/' ) );

		if ( count( $sults_parts ) > 0 ) {
			array_pop( $sults_parts );
		}

		if ( ! empty( $sults_parts ) && $sults_parts[0] === 'checklist' ) {
			if ( ! isset( $sults_parts[1] ) || $sults_parts[1] !== 'artigos' ) {
				array_splice( $sults_parts, 1, 0, 'artigos' );
			}
		}
		array_unshift( $sults_parts, 'sults', 'pages', 'produtos' );

		return implode( '/', $sults_parts );
	}
}
