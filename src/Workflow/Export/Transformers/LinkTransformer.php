<?php
/**
 * Transformador de Links (Âncoras).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export\Transformers
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Export\Transformers;

use Sults\Writen\Contracts\DomTransformerInterface;
use Sults\Writen\Contracts\ConfigProviderInterface;
use DOMDocument;
use DOMXPath;
use DOMElement;

/**
 * Classe LinkTransformer.
 *
 * Responsável por padronizar e proteger os hiperlinks no conteúdo exportado.
 *
 * Funcionalidades principais:
 * 1. Segurança: Adiciona `rel="noopener noreferrer"` automaticamente em links externos
 * para prevenir ataques de "Tabnabbing" reverso.
 * 2. UX: Força a abertura em nova aba (`target="_blank"`) se não especificado.
 * 3. Limpeza: Remove o atributo `title` que o WordPress adiciona automaticamente e
 * que é redundante para a acessibilidade moderna.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export\Transformers
 * @author     Sults
 * @since      0.1.0
 */
class LinkTransformer implements DomTransformerInterface {

	/**
	 * Configurações gerais (para verificar domínios internos).
	 * @var ConfigProviderInterface
	 */
	private ConfigProviderInterface $config;

	/**
	 * Construtor.
	 *
	 * @param ConfigProviderInterface $config Serviço de configuração.
	 */
	public function __construct( ConfigProviderInterface $config ) {
		$this->config = $config;
	}

	/**
	 * Transforma os elementos de link `<a>`.
	 *
	 * @since 0.1.0
	 *
	 * @param DOMDocument $dom   O documento DOM.
	 * @param DOMXPath    $xpath O objeto XPath.
	 * @return void
	 */
	public function transform( DOMDocument $dom, DOMXPath $xpath ): void {
		$links            = $xpath->query( '//a' );
		$internal_domains = $this->config->get_internal_domains();

		foreach ( $links as $link ) {
			if ( ! $link instanceof DOMElement ) {
				continue;
			}

			// Limpeza: Remove 'title' (redundante/ruído visual).
			$link->removeAttribute( 'title' );
			
			$href = trim( $link->getAttribute( 'href' ) );
			if ( ! $href ) {
				continue;
			}

			//Remove a barra final da URL, se existir (ex: /slug/ -> /slug).
			$href = rtrim( $href, '/' );
			$link->setAttribute( 'href', $href );

			// Ignora links funcionais que não são navegação (âncoras, email, telefone).
			if ( strpos( $href, '#' ) === 0 || stripos( $href, 'mailto:' ) === 0 || stripos( $href, 'tel:' ) === 0 ) {
				continue;
			}

			// UX: Garante que links abram em nova aba por padrão no sistema legado.
			if ( ! $link->hasAttribute( 'target' ) ) {
				$link->setAttribute( 'target', '_blank' );
			}

			// Verifica se o link é interno ou externo.
			$sults_parsed_url = wp_parse_url( $href );
			$host             = isset( $sults_parsed_url['host'] ) ? $sults_parsed_url['host'] : '';

			// Se não tem host (ex: '/pagina'), é interno.
			$is_internal = empty( $host );
			
			// Se tem host, verifica se está na lista de domínios permitidos.
			if ( ! $is_internal ) {
				foreach ( $internal_domains as $domain ) {
					if ( stripos( $host, $domain ) !== false ) {
						$is_internal = true;
						break;
					}
				}
			}

			// Segurança: Para links EXTERNOS, adiciona proteções.
			if ( ! $is_internal ) {
				$rel         = $link->getAttribute( 'rel' );
				$sults_parts = $rel ? explode( ' ', $rel ) : array();

				$changed = false;
				
				// 'noopener': Impede que a nova página acesse window.opener (segurança).
				if ( ! in_array( 'noopener', $sults_parts, true ) ) {
					$sults_parts[] = 'noopener';
					$changed       = true;
				}
				
				// 'noreferrer': Impede envio de cabeçalho Referer (privacidade).
				if ( ! in_array( 'noreferrer', $sults_parts, true ) ) {
					$sults_parts[] = 'noreferrer';
					$changed       = true;
				}

				if ( $changed ) {
					$link->setAttribute( 'rel', trim( implode( ' ', $sults_parts ) ) );
				}
			}
		}
	}
}