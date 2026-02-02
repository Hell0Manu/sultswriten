<?php
/**
 * Transformador de Tabelas.
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
 * Classe TableTransformer.
 *
 * Responsável por garantir que todas as tabelas HTML sejam responsivas no layout final.
 *
 * O sistema legado (e boas práticas de web design) exige que tabelas sejam envolvidas
 * em um container (`<div class="table-content">`) para permitir rolagem horizontal
 * em telas menores, sem quebrar a largura da página inteira.
 *
 * Este transformador varre o DOM e aplica esse invólucro automaticamente se ele
 * já não existir.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export\Transformers
 * @author     Sults
 * @since      0.1.0
 */
class TableTransformer implements DomTransformerInterface {

	/**
	 * Executa a transformação das tabelas.
	 *
	 * Lógica:
	 * 1. Encontra todas as tags `<table>`.
	 * 2. Verifica se o pai imediato já é uma `<div class="table-content">`.
	 * 3. Se não for, cria o wrapper e move a tabela para dentro dele.
	 *
	 * Entrada:
	 * <table>...</table>
	 *
	 * Saída:
	 * <div class="table-content">
	 * <table>...</table>
	 * </div>
	 *
	 * @since 0.1.0
	 *
	 * @param DOMDocument $dom   O documento DOM.
	 * @param DOMXPath    $xpath O objeto XPath.
	 * @return void
	 */
	public function transform( DOMDocument $dom, DOMXPath $xpath ): void {
		$tables = $xpath->query( '//table' );

		foreach ( $tables as $table ) {
			$sults_parent = $table->parentNode;

			// Verifica se já está envolvido corretamente para evitar dupla aplicação.
			if ( $sults_parent instanceof DOMElement &&
				 $sults_parent->nodeName === 'div' &&
				 $sults_parent->getAttribute( 'class' ) === 'table-content' 
			) {
				continue;
			}

			// Cria o container wrapper.
			$wrapper = $dom->createElement( 'div' );
			$wrapper->setAttribute( 'class', 'table-content' );

			// Realiza a troca no DOM:
			// 1. Substitui a tabela pelo wrapper no lugar onde a tabela estava.
			// 2. Move a tabela para dentro do wrapper.
			if ( $sults_parent ) {
				$sults_parent->replaceChild( $wrapper, $table );
				$wrapper->appendChild( $table );
			}
		}
	}
}