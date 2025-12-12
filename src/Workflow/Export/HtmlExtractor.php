<?php
namespace Sults\Writen\Workflow\Export;

use Sults\Writen\Contracts\HtmlExtractorInterface;
use Sults\Writen\Contracts\ConfigProviderInterface;
use Sults\Writen\Contracts\DomTransformerInterface;
use DOMDocument;
use DOMXPath;
use WP_Post;

class HtmlExtractor implements HtmlExtractorInterface {

	/**
	 * Lista de transformadores DOM.
	 *
	 * @var DomTransformerInterface[]
	 */
	private array $transformers;
	private ConfigProviderInterface $config;

	public function __construct( array $transformers, ConfigProviderInterface $config ) {
		$this->transformers = $transformers;
		$this->config       = $config;
	}

	/**
	 * Lista de classes do Gutenberg/WordPress que permitimos manter.
	 * Todo o resto será removido para limpar o HTML.
	 */
	private const ALLOWED_CLASSES = array(
		'wp-block-columns',
		'wp-block-column',
		'wp-block-image',
		'wp-block-table',
		'wp-block-separator',
		'wp-block-quote',
		'aligncenter',
		'alignleft',
		'alignright',
		'is-style-default',
		'has-text-align-center',
	);

	/**
	 * Executa o pipeline de extração: Obtém -> Limpa -> Melhora.
	 *
	 * @param WP_Post $post O post original.
	 * @return string O HTML final processado.
	 */
	public function extract( WP_Post $post ): string {
		$html = $post->post_content;

		$html = $this->remove_gutenberg_comments( $html );
		$html = $this->remove_classes_ids( $html );
		$html = $this->remove_tags_figures( $html );
		$html = $this->remove_empty_tags( $html );
		$html = $this->clear_white_spaces( $html );
		$html = $this->normalize_urls( $html );

		$html = $this->improve_markup_with_dom( $html );

		$html = str_replace( '"', "'", $html );

		return $html;
	}

	// MÉTODOS DE LIMPEZA (CLEANER).
	private function remove_gutenberg_comments( string $html ): string {
		return preg_replace( '/<!--.*?-->/s', '', $html );
	}

	private function remove_classes_ids( string $html ): string {
		$html = preg_replace( '/\s+id=["\'][^"\']*["\']/', '', $html );
		$html = preg_replace_callback(
			'/ class=["\'](.*?)["\']/',
			function ( $matches ) {
				$all_classes  = explode( ' ', $matches[1] );
				$kept_classes = array();
				foreach ( $all_classes as $cls ) {
					$cls = trim( $cls );
					if ( in_array( $cls, self::ALLOWED_CLASSES, true ) ) {
						$kept_classes[] = $cls;
					}
				}
				if ( empty( $kept_classes ) ) {
					return '';
				}
				return ' class="' . implode( ' ', $kept_classes ) . '"';
			},
			$html
		);
		return $html ?? '';
	}

	private function remove_tags_figures( string $html ): string {
		return preg_replace( '/<\/?figure[^>]*>|<figcaption[^>]*>.*?<\/figcaption>/is', '', $html ) ?? $html;
	}

	private function remove_empty_tags( string $html ): string {
		$pattern = '/<(p|h[1-6]|span|li)[^>]*>(?:\s|&nbsp;|&#160;)*<\/\1>/iu';
		$html    = preg_replace( $pattern, '', $html );
		return preg_replace( $pattern, '', $html ) ?? $html;
	}

	private function clear_white_spaces( string $html ): string {
		return preg_replace( '/(\R\s*)+/', "\n\n", $html ) ?? $html;
	}

	private function normalize_urls( string $html ): string {
		$domain = preg_quote( $this->config->get_internal_domain(), '#' );

		// Ex: #https://www\.sults\.com\.br(?!/)(?=")#.
		$html = preg_replace( "#https://(?:www\.)?{$domain}(?!/)(?=\")#", '/', $html );
		$html = preg_replace( "#https://(?:www\.)?{$domain}/#", '/', $html );

		return $html ?? '';
	}


	// MÉTODOS DE MELHORIA (IMPROVER).
	private function improve_markup_with_dom( string $html ): string {
		if ( empty( $html ) ) {
			return '';
		}

		$dom = new DOMDocument();

		libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();

		$xpath = new DOMXPath( $dom );

		foreach ( $this->transformers as $transformer ) {
			$transformer->transform( $dom, $xpath );
		}

		$output = $dom->saveHTML();
		if ( ! $output ) {
			return '';
		}

		return html_entity_decode( $output, ENT_QUOTES, 'UTF-8' );
	}
}
