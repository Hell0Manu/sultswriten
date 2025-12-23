<?php
namespace Sults\Writen\Workflow\Export;

use Sults\Writen\Contracts\HtmlExtractorInterface;
use Sults\Writen\Contracts\ConfigProviderInterface;
use Sults\Writen\Contracts\DomTransformerInterface;
use DOMDocument;
use DOMXPath;
use DOMElement;
use DOMNode;
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
	 * Executa o pipeline de extração: Obtém -> Limpa -> Melhora.
	 *
	 * @param WP_Post $post O post original.
	 * @return string O HTML final processado.
	 */
	public function extract( WP_Post $post ): string {
		$html = $post->post_content;

		$html = $this->remove_gutenberg_comments( $html );
		$html = $this->clear_white_spaces( $html );
		$html = $this->normalize_urls( $html );
		$html = $this->process_with_dom( $html );

		return $html;
	}

	private function remove_gutenberg_comments( string $html ): string {
		return preg_replace( '/<!--.*?-->/s', '', $html );
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
		$domains = $this->config->get_internal_domains();

		foreach ( $domains as $domain ) {
			$quoted_domain = preg_quote( $domain, '#' );

			// #https?://      -> Busca por http ou https
			// (?:www\.)?      -> Busca opcional por www.
			// {$quoted_domain}-> O domínio da vez (ex: artigo.sults.com.br)
			// (?!/)           -> Garante que não estamos pegando algo que já é relativo
			
			// https://sults.com.br -> /
			$html = preg_replace( "#https?://(?:www\.)?{$quoted_domain}(?!/)(?=\")#", '/', $html );
			
			// https://sults.com.br/ -> /
			$html = preg_replace( "#https?://(?:www\.)?{$quoted_domain}/#", '/', $html );
		}

		return $html ?? '';
	}

	/**
	 * Carrega o HTML no DOM, aplica limpeza e transformadores.
	 */
	private function process_with_dom( string $html ): string {
		if ( empty( $html ) ) {
			return '';
		}

		$dom = new DOMDocument();

		libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();

		$xpath = new DOMXPath( $dom );

		$this->clean_classes_and_ids( $xpath );
		$this->clean_figures( $dom, $xpath );
		$this->clean_empty_tags( $xpath );
		$this->clean_strong_in_headings( $xpath );

		foreach ( $this->transformers as $transformer ) {
			$transformer->transform( $dom, $xpath );
		}

		$output = $dom->saveHTML();

		if ( ! $output ) {
			return '';
		}
		$output = preg_replace( '/^<\?xml.+?\?>\s*/i', '', $output );

		return html_entity_decode( $output, ENT_QUOTES, 'UTF-8' );
	}

	private function clean_strong_in_headings( DOMXPath $xpath ): void {
   		$strongs = $xpath->query( '//h1//strong | //h2//strong | //h3//strong | //h4//strong | //h5//strong | //h6//strong' );

		foreach ( $strongs as $strong ) {
			if ( ! $strong instanceof DOMElement ) {
				continue;
			}

			$parent = $strong->parentNode;
			while ( $strong->firstChild ) {
				$parent->insertBefore( $strong->firstChild, $strong );
			}
			$parent->removeChild( $strong );
		}
	}

	/**
	 * Remove IDs e filtra classes permitidas.
	 */
	private function clean_classes_and_ids( DOMXPath $xpath ): void {
		$nodes = $xpath->query( '//*[@id] | //*[@class]' );

		foreach ( $nodes as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}

			$node->removeAttribute( 'id' );

			if ( $node->hasAttribute( 'class' ) ) {
				$raw_classes = $node->getAttribute( 'class' );
				$classes     = array_filter( explode( ' ', $raw_classes ) );
				$kept        = array();

				foreach ( $classes as $c ) {
					$c = trim( $c );
					if ( in_array( $c, ExportConfig::ALLOWED_CLASSES, true ) ) {
						$kept[] = $c;
					}
				}

				if ( ! empty( $kept ) ) {
					$node->setAttribute( 'class', implode( ' ', $kept ) );
				} else {
					$node->removeAttribute( 'class' );
				}
			}
		}
	}

	/**
	 * Remove tags <figure> (mantendo conteúdo) e remove <figcaption> (inteiro).
	 */
	private function clean_figures( DOMDocument $dom, DOMXPath $xpath ): void {
		$captions = $xpath->query( '//figcaption' );
		foreach ( $captions as $caption ) {
			$caption->parentNode->removeChild( $caption );
		}

		$figures = $xpath->query( '//figure' );
		foreach ( $figures as $figure ) {
			if ( ! $figure instanceof DOMElement ) {
				continue;
			}

			while ( $figure->firstChild ) {
				$figure->parentNode->insertBefore( $figure->firstChild, $figure );
			}
			$figure->parentNode->removeChild( $figure );
		}
	}

	/**
	 * Remove tags vazias específicas (p, h1-h6, span, li).
	 */
	private function clean_empty_tags( DOMXPath $xpath ): void {

		$query = '//p | //h1 | //h2 | //h3 | //h4 | //h5 | //h6 | //span | //li';
		$nodes = $xpath->query( $query );

		for ( $i = $nodes->length - 1; $i >= 0; $i-- ) {
			$node = $nodes->item( $i );

			if ( $this->has_element_children( $node ) ) {
				continue;
			}

			$text = trim( $node->textContent );
			$text = str_replace( "\xc2\xa0", '', $text );
			$text = trim( $text );

			if ( empty( $text ) ) {
				$node->parentNode->removeChild( $node );
			}
		}
	}

	private function has_element_children( DOMNode $node ): bool {
		if ( ! $node->hasChildNodes() ) {
			return false;
		}
		foreach ( $node->childNodes as $child ) {
			if ( $child->nodeType === XML_ELEMENT_NODE ) {
				return true;
			}
		}
		return false;
	}
}