<?php
/**
 * Extrator e Limpador de HTML.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Export;

use Sults\Writen\Contracts\HtmlExtractorInterface;
use Sults\Writen\Contracts\ConfigProviderInterface;
use Sults\Writen\Contracts\DomTransformerInterface;
use DOMDocument;
use DOMXPath;
use DOMElement;
use DOMNode;
use WP_Post;

/**
 * Classe HtmlExtractor.
 *
 * Responsável por obter o conteúdo do post e aplicar uma série de filtros de limpeza
 * para garantir que o HTML resultante seja compatível com o sistema legado (JSP).
 *
 * O processo ocorre em duas fases:
 * 1. Processamento de Texto (Regex): Remove comentários do Gutenberg, normaliza URLs e espaços.
 * 2. Processamento DOM (DOMDocument): Realiza manipulações estruturais complexas,
 * como remover IDs, filtrar classes CSS permitidas e desembrulhar tags indesejadas.
 *
 * @see ExportConfig
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export
 * @author     Sults
 * @since      0.1.0
 */
class HtmlExtractor implements HtmlExtractorInterface {

	/**
	 * Lista de transformadores DOM externos.
	 * Permite injetar lógicas de transformação específicas sem poluir esta classe.
	 *
	 * @var DomTransformerInterface[]
	 */
	private array $transformers;

	/**
	 * Provedor de configurações (ex: domínios internos).
	 * @var ConfigProviderInterface
	 */
	private ConfigProviderInterface $config;

	/**
	 * Construtor.
	 *
	 * @param DomTransformerInterface[] $transformers Array de objetos transformadores.
	 * @param ConfigProviderInterface   $config       Configurações gerais.
	 */
	public function __construct( array $transformers, ConfigProviderInterface $config ) {
		$this->transformers = $transformers;
		$this->config       = $config;
	}

	/**
	 * Executa o pipeline de extração completo.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Post $sults_post O post original do WordPress.
	 * @return string O HTML final limpo e processado.
	 */
	public function extract( WP_Post $sults_post ): string {
		$html = $sults_post->post_content;

		// Fase 1: Limpeza baseada em strings/regex.
		$html = $this->remove_gutenberg_comments( $html );
		$html = $this->clear_white_spaces( $html );
		$html = $this->normalize_urls( $html );

		// Fase 2: Manipulação estrutural via DOM.
		$html = $this->process_with_dom( $html );

		return $html;
	}

	/**
	 * Remove os comentários HTML usados pelo editor de blocos (Gutenberg).
	 * Ex: */
	private function remove_gutenberg_comments( string $html ): string {
		return preg_replace( '//s', '', $html );
	}

	/**
	 * Remove tags <figure> e <figcaption> via Regex (Fallback/Pré-processamento).
	 * Nota: A limpeza principal disso é feita no DOM, mas isso ajuda a limpar o código antes.
	 */
	private function remove_tags_figures( string $html ): string {
		return preg_replace( '/<\/?figure[^>]*>|<figcaption[^>]*>.*?<\/figcaption>/is', '', $html ) ?? $html;
	}

	/**
	 * Remove tags vazias que sobraram da edição (ex: <p>&nbsp;</p>).
	 */
	private function remove_empty_tags( string $html ): string {
		$sults_pattern = '/<(p|h[1-6]|span|li)[^>]*>(?:\s|&nbsp;|&#160;)*<\/\1>/iu';
		$html          = preg_replace( $sults_pattern, '', $html );
		return preg_replace( $sults_pattern, '', $html ) ?? $html;
	}

	/**
	 * Normaliza quebras de linha excessivas.
	 */
	private function clear_white_spaces( string $html ): string {
		return preg_replace( '/(\R\s*)+/', "\n\n", $html ) ?? $html;
	}

	/**
	 * Converte URLs absolutas internas em caminhos relativos.
	 *
	 * Ex: 'https://ajuda.sults.com.br/artigo-x' vira '/artigo-x'.
	 * Isso garante que os links funcionem em qualquer ambiente (dev/homolog/prod).
	 */
	private function normalize_urls( string $html ): string {
		$domains = $this->config->get_internal_domains();

		foreach ( $domains as $domain ) {
			$quoted_domain = preg_quote( $domain, '#' );

			// Regex complexa para substituir o domínio apenas se não for seguido por barra (evita duplas).
			// #https?://      -> Busca por http ou https.
			// (?:www\.)?      -> Busca opcional por www.
			// {$quoted_domain}-> O domínio da vez (ex: artigo.sults.com.br).
			
			$html = preg_replace( "#https?://(?:www\.)?{$quoted_domain}(?!/)(?=\")#", '/', $html );
			$html = preg_replace( "#https?://(?:www\.)?{$quoted_domain}/#", '/', $html );
		}

		return $html ?? '';
	}

	/**
	 * Carrega o HTML no DOMDocument para manipulação segura.
	 *
	 * Configura o libxml para não adicionar tags <html> ou <body> automaticamente
	 * e lidar com erros de parsing de HTML5 silenciosamente.
	 */
	private function process_with_dom( string $html ): string {
		if ( empty( $html ) ) {
			return '';
		}

		$dom = new DOMDocument();

		// Suprime erros de parsing de HTML5 malformado.
		libxml_use_internal_errors( true );
		// Carrega como UTF-8 forçado e sem adicionar DTD/HTML/BODY implícitos.
		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();

		$xpath = new DOMXPath( $dom );

		// Aplica as limpezas estruturais.
		$this->clean_classes_and_ids( $xpath );
		$this->clean_figures( $dom, $xpath );
		$this->clean_empty_tags( $xpath );
		$this->clean_strong_in_headings( $xpath );

		// Executa transformadores externos (ex: TableTransformer, ImageTransformer).
		foreach ( $this->transformers as $transformer ) {
			$transformer->transform( $dom, $xpath );
		}

		$output = $dom->saveHTML();

		if ( ! $output ) {
			return '';
		}
		// Remove a declaração XML adicionada pelo hack de encoding.
		$output = preg_replace( '/^<\?xml.+?\?>\s*/i', '', $output );

		return html_entity_decode( $output, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Remove tags <strong> de dentro de cabeçalhos (H1-H6).
	 * Regra de estilo: Cabeçalhos já são negrito por padrão.
	 */
	private function clean_strong_in_headings( DOMXPath $xpath ): void {
		$strongs = $xpath->query( '//h1//strong | //h2//strong | //h3//strong | //h4//strong | //h5//strong | //h6//strong' );

		foreach ( $strongs as $strong ) {
			if ( ! $strong instanceof DOMElement ) {
				continue;
			}

			// Move os filhos do strong para o pai (unwrap) e remove o strong.
			$sults_parent = $strong->parentNode;
			while ( $strong->firstChild ) {
				$sults_parent->insertBefore( $strong->firstChild, $strong );
			}
			$sults_parent->removeChild( $strong );
		}
	}

	/**
	 * Sanitização de atributos CSS e IDs.
	 * 1. Remove todos os IDs (para evitar conflitos na página de destino).
	 * 2. Filtra as classes CSS mantendo apenas as permitidas em ExportConfig.
	 */
	private function clean_classes_and_ids( DOMXPath $xpath ): void {
		$nodes = $xpath->query( '//*[@id] | //*[@class]' );

		foreach ( $nodes as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}

			// Remove ID.
			$node->removeAttribute( 'id' );

			// Filtra Classes.
			if ( $node->hasAttribute( 'class' ) ) {
				$raw_classes = $node->getAttribute( 'class' );
				$classes     = array_filter( explode( ' ', $raw_classes ) );
				$kept        = array();

				foreach ( $classes as $c ) {
					$c = trim( $c );
					// Verifica na Allowlist.
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
	 * Remove tags <figure> (desembrulhando o conteúdo) e remove <figcaption> completamente.
	 * O sistema legado não suporta a tag figure do HTML5 bem.
	 */
	private function clean_figures( DOMDocument $dom, DOMXPath $xpath ): void {
		// Remove legendas.
		$captions = $xpath->query( '//figcaption' );
		foreach ( $captions as $caption ) {
			$caption->parentNode->removeChild( $caption );
		}

		// Desembrulha figures (mantém a imagem/tabela dentro, remove a tag figure).
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
	 * Remove tags estruturais vazias que não contêm texto visível.
	 * Lida com espaços não quebráveis (&nbsp;) que o editor insere.
	 */
	private function clean_empty_tags( DOMXPath $xpath ): void {
		$query = '//p | //h1 | //h2 | //h3 | //h4 | //h5 | //h6 | //span | //li';
		$nodes = $xpath->query( $query );

		// Itera de trás para frente para evitar problemas ao remover nós durante o loop.
		for ( $i = $nodes->length - 1; $i >= 0; $i-- ) {
			$node = $nodes->item( $i );

			// Se tiver filhos que são Elementos (ex: img, strong), não é vazio.
			if ( $this->has_element_children( $node ) ) {
				continue;
			}

			// Limpa espaços e non-breaking spaces.
			$text = trim( $node->textContent );
			$text = str_replace( "\xc2\xa0", '', $text );
			$text = trim( $text );

			if ( empty( $text ) ) {
				$node->parentNode->removeChild( $node );
			}
		}
	}

	/**
	 * Verifica se um nó possui elementos HTML filhos (ignorando nós de texto).
	 */
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