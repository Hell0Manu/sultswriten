<?php
/**
 * Transformador de Imagens (SEO e Dimensões).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export\Transformers
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Export\Transformers;

use Sults\Writen\Contracts\DomTransformerInterface;
use Sults\Writen\Contracts\AttachmentProviderInterface;
use Sults\Writen\Contracts\ConfigProviderInterface;
use DOMDocument;
use DOMXPath;
use DOMElement;

/**
 * Classe ImageTransformer.
 *
 * Responsável pelo enriquecimento e padronização das tags `<img>`.
 *
 * Funcionalidades principais:
 * 1. Otimização de SEO: Preenche atributos `alt` e `title` vazios usando o contexto
 * (cabeçalho H2 anterior ou título do post).
 * 2. Performance: Adiciona `loading="lazy"`.
 * 3. Layout: Recalcula `width` e `height` para forçar a largura padrão do layout legado (850px)
 * mantendo a proporção (aspect ratio) correta.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export\Transformers
 * @author     Sults
 * @since      0.1.0
 */
class ImageTransformer implements DomTransformerInterface {

	/** @var AttachmentProviderInterface Serviço para obter metadados da imagem (dimensões reais). */
	private AttachmentProviderInterface $attachment_provider;

	/** @var ConfigProviderInterface Configurações gerais (URL do site). */
	private ConfigProviderInterface $config;

	/**
	 * Construtor.
	 */
	public function __construct(
		AttachmentProviderInterface $attachment_provider,
		ConfigProviderInterface $config
	) {
		$this->attachment_provider = $attachment_provider;
		$this->config              = $config;
	}

	/**
	 * Executa as transformações nas imagens.
	 *
	 * Filtra apenas imagens internas (hospedadas no próprio site) e ignora SVGs.
	 *
	 * @since 0.1.0
	 *
	 * @param DOMDocument $dom   O documento DOM.
	 * @param DOMXPath    $xpath O objeto XPath.
	 * @return void
	 */
	public function transform( DOMDocument $dom, DOMXPath $xpath ): void {
		$images     = $xpath->query( '//img' );
		$home_url   = $this->config->get_home_url();
		$upload_dir = wp_upload_dir();

		foreach ( $images as $img ) {
			if ( ! $img instanceof DOMElement ) {
				continue;
			}

			$src = trim( $img->getAttribute( 'src' ) );
			if ( ! $src ) {
				continue;
			}

			// Ignora vetores (SVG) pois o redimensionamento pixel-based não se aplica da mesma forma.
			if ( stripos( $src, '.svg' ) !== false ) {
				continue;
			}

			// Processa apenas imagens internas.
			$is_internal = ( strpos( $src, $home_url ) !== false ) || ( substr( $src, 0, 1 ) === '/' );
			if ( ! $is_internal ) {
				continue;
			}

			// --- LÓGICA DE SEO ---
			// Tenta encontrar um H2 imediatamente antes da imagem para usar como legenda/alt.
			$headings = $xpath->query(
				'preceding::h2[1]',
				$img
			);

			$alt_text = '';

			if ( $headings->length > 0 && $headings->item( 0 ) ) {
				$alt_text = trim( $headings->item( 0 )->textContent );
			} else {
				// Fallback: Título do Post.
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$sults_post_id    = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
				$sults_post_title = $sults_post_id ? get_the_title( $sults_post_id ) : '';

				if ( ! empty( $sults_post_title ) && stripos( $sults_post_title, 'untitled' ) === false ) {
					$alt_text = $sults_post_title;
				} else {
					// Último recurso: Nome do arquivo.
					$path_info = pathinfo( (string) wp_parse_url( $src, PHP_URL_PATH ), PATHINFO_FILENAME );
					$alt_text  = $path_info; // Atribui nome do arquivo caso nada mais funcione.
				}
			}

			$img->setAttribute( 'alt', $alt_text );
			$img->setAttribute( 'title', $alt_text );

			// --- PERFORMANCE ---
			if ( ! $img->hasAttribute( 'loading' ) ) {
				$img->setAttribute( 'loading', 'lazy' );
			}

			// --- DIMENSÕES E LAYOUT ---
			$original_width  = 0;
			$original_height = 0;

			// Tenta obter dimensões via banco de dados do WP (mais rápido/seguro).
			$attachment_id = $this->attachment_provider->get_attachment_id_by_url( $src );

			if ( $attachment_id ) {
				$image_data = $this->attachment_provider->get_image_src( $attachment_id, 'full' );
				if ( $image_data ) {
					$original_width  = (int) $image_data[1];
					$original_height = (int) $image_data[2];
				}
			}

			// Fallback: Tenta ler o arquivo físico se não achou no banco.
			if ( $original_width === 0 ) {
				$local_path = $this->resolve_local_path_fallback( $src, $upload_dir );

				if ( $local_path && file_exists( $local_path ) ) {
					// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- getimagesize pode emitir warnings em arquivos corrompidos.
					$size = @getimagesize( $local_path );
					if ( $size ) {
						$original_width  = $size[0];
						$original_height = $size[1];
					}
				}
			}

			// Aplica redimensionamento proporcional se conseguimos as dimensões originais.
			if ( $original_width > 0 && $original_height > 0 ) {
				// Largura padrão do layout legado.
				$target_width = 850;
				// Regra de 3 para calcular altura proporcional.
				$target_height = round( ( $original_height / $original_width ) * $target_width );

				$img->setAttribute( 'width', (string) $target_width );
				$img->setAttribute( 'height', (string) $target_height );
			}
		}
	}

	/**
	 * Tenta localizar o arquivo físico no disco para leitura de metadados.
	 *
	 * Útil quando a imagem não está registrada na Media Library (ex: enviada via FTP)
	 * ou quando a busca por URL falha.
	 *
	 * @param string $url        URL da imagem.
	 * @param array  $upload_dir Dados do diretório de upload (wp_upload_dir).
	 * @return string|null Caminho absoluto ou null.
	 */
	private function resolve_local_path_fallback( string $url, array $upload_dir ): ?string {
		$url        = urldecode( $url );
		$sults_path = wp_parse_url( $url, PHP_URL_PATH );

		if ( empty( $sults_path ) ) {
			return null;
		}

		// Tenta mapear uploads padrão.
		if ( strpos( $sults_path, '/wp-content/uploads' ) !== false ) {
			$sults_parts = explode( '/wp-content/uploads', $sults_path );
			if ( isset( $sults_parts[1] ) ) {
				return $upload_dir['basedir'] . $sults_parts[1];
			}
		}

		// Tenta mapear qualquer coisa em wp-content (ex: plugins/themes).
		if ( strpos( $sults_path, '/wp-content' ) !== false ) {
			$sults_parts = explode( '/wp-content', $sults_path );
			if ( isset( $sults_parts[1] ) ) {
				return WP_CONTENT_DIR . $sults_parts[1];
			}
		}

		return null;
	}
}
