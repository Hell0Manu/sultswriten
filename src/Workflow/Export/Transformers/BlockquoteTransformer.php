<?php
/**
 * Transformador de Citações (Blockquotes).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export\Transformers
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Export\Transformers;

use Sults\Writen\Contracts\DomTransformerInterface;
use DOMDocument;
use DOMXPath;
use DOMElement;

/**
 * Classe BlockquoteTransformer.
 *
 * Responsável por normalizar a estrutura HTML das citações.
 *
 * O editor do WordPress geralmente insere a citação (`<cite>`) diretamente dentro
 * do `blockquote`. Este transformador reestrutura o HTML para atender ao padrão
 * visual do sistema legado, movendo a autoria para uma tag `<footer>`.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export\Transformers
 * @author     Sults
 * @since      0.1.0
 */
class BlockquoteTransformer implements DomTransformerInterface {

	/**
	 * Aplica a transformação nas tags <blockquote>.
	 *
	 * Lógica de Transformação:
	 * 1. Encontra citações que possuem uma tag `<cite>`.
	 * 2. Extrai o texto (esperado formato: "Nome do Autor, Cargo").
	 * 3. Cria um elemento `<footer>` e adiciona o prefixo "—".
	 * 4. Se houver vírgula, separa o Nome do Cargo (colocando o cargo em novo `<cite>`).
	 *
	 * Entrada:
	 * <blockquote>Texto <cite>Fulano, CEO</cite></blockquote>
	 *
	 * Saída:
	 * <blockquote>Texto <footer>— Fulano, <cite>CEO</cite>.</footer></blockquote>
	 *
	 * @since 0.1.0
	 *
	 * @param DOMDocument $dom   O documento DOM sendo processado.
	 * @param DOMXPath    $xpath O objeto XPath para consultas.
	 * @return void
	 */
	public function transform( DOMDocument $dom, DOMXPath $xpath ): void {
		$quotes = $xpath->query( '//blockquote' );

		foreach ( $quotes as $quote ) {
			if ( ! $quote instanceof DOMElement ) {
				continue;
			}

			// Procura por tag <cite> existente (padrão WP).
			$cites = $quote->getElementsByTagName( 'cite' );
			if ( $cites->length === 0 ) {
				continue;
			}

			$old_cite = $cites->item( 0 );
			if ( ! $old_cite ) {
				continue;
			}

			// Remove a formatação antiga para reconstruir.
			$full_text = rtrim( trim( $old_cite->textContent ), ' .' );

			if ( $old_cite->parentNode ) {
				$old_cite->parentNode->removeChild( $old_cite );
			}

			// Cria a nova estrutura de rodapé da citação.
			$footer = $dom->createElement( 'footer' );
			
			// Tenta separar Autor de Cargo pela vírgula.
			$sults_parts = explode( ',', $full_text, 2 );
			$name        = trim( $sults_parts[0] );

			// Adiciona o travessão e o nome.
			$footer->appendChild( $dom->createTextNode( "—{$name}" ) );

			// Se houver cargo (parte 2), adiciona formatado.
			if ( isset( $sults_parts[1] ) ) {
				$footer->appendChild( $dom->createTextNode( ', ' ) );
				$role_cite = $dom->createElement( 'cite', trim( $sults_parts[1] ) );
				$footer->appendChild( $role_cite );
			}
			
			// Fecha com ponto final.
			$footer->appendChild( $dom->createTextNode( '.' ) );
			
			// Anexa ao blockquote.
			$quote->appendChild( $footer );
		}
	}
}