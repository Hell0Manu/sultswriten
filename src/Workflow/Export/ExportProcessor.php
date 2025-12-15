<?php
namespace Sults\Writen\Workflow\Export;

use WP_Post;
use Sults\Writen\Contracts\HtmlExtractorInterface;
use Sults\Writen\Contracts\JspBuilderInterface;
use Sults\Writen\Contracts\SeoDataProviderInterface;
use Sults\Writen\Workflow\Export\ExportAssetsManager;
use Sults\Writen\Workflow\Export\ExportPayload;

/**
 * Responsável por orquestrar todo o processo de geração de conteúdo para exportação.
 * Combina: Extração HTML -> Processamento de Assets -> Geração de JSP.
 */
class ExportProcessor {

	private HtmlExtractorInterface $extractor;
	private ExportAssetsManager $assets_manager;
	private SeoDataProviderInterface $seo_provider;
	private JspBuilderInterface $jsp_builder;

	public function __construct(
		HtmlExtractorInterface $extractor,
		ExportAssetsManager $assets_manager,
		SeoDataProviderInterface $seo_provider,
		JspBuilderInterface $jsp_builder
	) {
		$this->extractor      = $extractor;
		$this->assets_manager = $assets_manager;
		$this->seo_provider   = $seo_provider;
		$this->jsp_builder    = $jsp_builder;
	}

	/**
	 * Processa um post e retorna os dados finais (JSP e arquivos).
	 *
	 * @param int    $post_id           O ID do post.
	 * @param string $zip_folder_prefix O prefixo das pastas dentro do ZIP (ex: "slug/images/").
	 * @return array Retorna ['jsp_content' => string, 'files_map' => array, 'html_clean' => string, 'html_raw' => string]
	 * @throws \InvalidArgumentException Se o post não for encontrado.
	 */
	public function execute( int $post_id, string $zip_folder_prefix ): array {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			throw new \InvalidArgumentException( 'Post não encontrado.' );
		}

		$html_raw   = $post->post_content;
		$html_clean = $this->extractor->extract( $post );

		/* @var ExportPayload $assets_payload */
		$assets_payload = $this->assets_manager->process( $html_clean, $zip_folder_prefix );

		$final_html_for_jsp = $assets_payload->html_content;
		$files_to_zip       = $assets_payload->files_to_zip;

		$seo_data   = $this->seo_provider->get_seo_data( $post_id );
		$page_title = get_the_title( $post );

		$jsp_content = $this->jsp_builder->build( $final_html_for_jsp, $page_title, $seo_data );

		return array(
			'jsp_content' => $jsp_content,
			'files_map'   => $files_to_zip,
			'html_clean'  => $html_clean,
			'html_raw'    => $html_raw,
		);
	}
}
