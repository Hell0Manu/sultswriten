<?php
/**
 * Processador de Exportação de Conteúdo.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export
 * @since      0.1.0
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

/**
 * Classe ExportProcessor.
 *
 * Orquestrador principal do fluxo de exportação.
 *
 * Responsável por receber um ID de post e transformar seu conteúdo bruto do WordPress
 * em um pacote completo pronto para ser compactado (ZIP).
 *
 * Etapas do Processo:
 * 1. Extração: Limpa o HTML do WordPress (`HtmlExtractor`).
 * 2. Assets: Baixa imagens remotas e reescreve URLs para locais (`ExportAssetsManager`).
 * 3. Sanitização: Garante que o HTML seja compatível com JSP (`JspHtmlSanitizer`).
 * 4. Metadados: Coleta dados de SEO e categorias.
 * 5. Construção: Gera o código JSP final (`JspBuilder`) e arquivo de informações (`ExportMetadataBuilder`).
 * 6. Caminhos: Calcula a estrutura de diretórios final baseada na hierarquia do post.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export
 * @author     Sults
 * @since      0.1.0
 */
class ExportProcessor {

	/** @var HtmlExtractorInterface Extrator de conteúdo limpo. */
	private HtmlExtractorInterface $extractor;

	/** @var ExportAssetsManager Gerenciador de imagens/anexos. */
	private ExportAssetsManager $assets_manager;

	/** @var SeoDataProviderInterface Provedor de dados Yoast/RankMath. */
	private SeoDataProviderInterface $seo_provider;

	/** @var JspBuilderInterface Construtor do template JSP. */
	private JspBuilderInterface $jsp_builder;

	/** @var JspHtmlSanitizerInterface Sanitizador de tags HTML para JSP. */
	private JspHtmlSanitizerInterface $sanitizer;

	/** @var ExportMetadataBuilder Construtor do arquivo info.json/txt. */
	private ExportMetadataBuilder $metadata_builder;

	/**
	 * Construtor com injeção de todas as dependências do pipeline.
	 */
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

	/**
	 * Executa o processo de exportação para um post específico.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $sults_post_id     ID do post a ser exportado.
	 * @param string $zip_folder_prefix Prefixo para as pastas de imagem dentro do ZIP.
	 * @return array Payload com conteúdo JSP, mapa de arquivos e metadados.
	 * @throws \InvalidArgumentException Se o post não existir.
	 */
	public function execute( int $sults_post_id, string $zip_folder_prefix ): array {
		$sults_post = get_post( $sults_post_id );
		
		if ( ! $sults_post instanceof WP_Post ) {
			throw new \InvalidArgumentException( 'Post não encontrado.' );
		}
		
		$sults_slug = $sults_post->post_name;

		// 1. Extração do HTML Limpo.
		$html_raw   = $sults_post->post_content;
		$html_clean = $this->extractor->extract( $sults_post );

		// 2. Processamento de Imagens (Download e Rewrite de URLs).
		/* @var ExportPayload $assets_payload */
		$assets_payload = $this->assets_manager->process( $html_clean, $zip_folder_prefix );

		$final_html_for_jsp = $assets_payload->html_content;
		$files_to_zip       = $assets_payload->files_to_zip;

		// 3. Sanitização e SEO.
		$safe_html_for_jsp = $this->sanitizer->sanitize( $final_html_for_jsp );
		$seo_data          = $this->seo_provider->get_seo_data( $sults_post_id );
		$sults_page_title  = get_the_title( $sults_post );

		// 4. Determinação do 'Active Group Name' (Menu Lateral Ativo).
		// Lógica: Pega a primeira categoria pai para definir qual menu deve ficar aberto no JSP.
		$active_group_name = '';
		$sults_cats        = get_the_category( $sults_post_id );

		if ( ! empty( $sults_cats ) && ! is_wp_error( $sults_cats ) ) {
			foreach ( $sults_cats as $sults_cat ) {
				if ( $sults_cat->parent > 0 ) {
					$active_group_name = $sults_cat->name;
					break;
				}
			}
		}

		// Determina o nome do arquivo final (JSP).
		if ( empty( $active_group_name ) ) {
			// Fallback para páginas raiz/gerais.
			$target_filename = 'visao-geral';
		} else {
			$target_filename = str_replace( '_', '-', $sults_slug );
		}

		// 5. Construção dos Arquivos Finais.
		$jsp_content = $this->jsp_builder->build(
			$safe_html_for_jsp,
			$sults_page_title,
			$target_filename,
			$seo_data,
			$active_group_name
		);

		$info_content    = $this->metadata_builder->build_info_file( $sults_post, $target_filename );
		$jsp_folder_path = $this->calculate_jsp_folder_path( $sults_post_id );

		return array(
			'jsp_content'        => $jsp_content,
			'info_content'       => $info_content,
			'files_map'          => $files_to_zip,
			'html_clean'         => $html_clean,
			'html_raw'           => $html_raw,
			'jsp_folder_path'    => $jsp_folder_path,
			'suggested_filename' => $target_filename,
		);
	}

	/**
	 * Calcula o caminho relativo da pasta dentro do projeto Java.
	 *
	 * Transforma a hierarquia do WordPress em uma estrutura de diretórios compatível
	 * com o sistema legado (sults/pages/produtos/...).
	 *
	 * Exemplo:
	 * WP:   /checklist/faq/solucao/implantacao-de-software
	 * Java: sults/pages/produtos/checklist/artigos/faq/solucao
	 *
	 * Nota: Adiciona a pasta 'artigos' artificialmente se estiver no módulo 'checklist',
	 * conforme regra de negócio específica.
	 *
	 * @since 0.1.0
	 * @param int $sults_post_id ID do post.
	 * @return string Caminho da pasta (sem o arquivo final).
	 */
	private function calculate_jsp_folder_path( int $sults_post_id ): string {
		$relative_path = PathHelper::get_relative_path( $sults_post_id );
		$sults_parts   = explode( '/', trim( $relative_path, '/' ) );

		// Remove o último segmento (slug do post) para pegar apenas a pasta.
		if ( count( $sults_parts ) > 0 ) {
			array_pop( $sults_parts );
		}

		// Regra Especial: Injeção da pasta 'artigos' no módulo Checklist.
		if ( ! empty( $sults_parts ) && $sults_parts[0] === 'checklist' ) {
			if ( ! isset( $sults_parts[1] ) || $sults_parts[1] !== 'artigos' ) {
				array_splice( $sults_parts, 1, 0, 'artigos' );
			}
		}

		// Prefixo padrão do projeto Java.
		array_unshift( $sults_parts, 'sults', 'pages', 'produtos' );

		return implode( '/', $sults_parts );
	}
}
