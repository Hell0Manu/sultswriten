<?php
/**
 * Transformador de Grade (Grid/Colunas).
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
 * Classe GridTransformer.
 *
 * Responsável por adaptar a estrutura de colunas do Gutenberg para o CSS do sistema legado.
 *
 * O editor de blocos usa `wp-block-columns` (linha) e `wp-block-column` (coluna).
 * O sistema de destino utiliza uma nomenclatura ligeiramente diferente (`block-columns`, `block-column`).
 * Este transformador realiza a substituição das classes para garantir que o layout
 * não quebre na exportação.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export\Transformers
 * @author     Sults
 * @since      0.1.0
 */
class GridTransformer implements DomTransformerInterface {

	/**
	 * Executa a transformação das classes de grid.
	 *
	 * Varre o DOM em busca dos containers de linha e coluna e aplica a troca de classes.
	 *
	 * @since 0.1.0
	 *
	 * @param DOMDocument $dom   O documento DOM.
	 * @param DOMXPath    $xpath O objeto XPath.
	 * @return void
	 */
	public function transform( DOMDocument $dom, DOMXPath $xpath ): void {
		// 1. Processa as Linhas (Containers).
		// Busca elementos que tenham a classe 'wp-block-columns'.
		$rows = $xpath->query( '//*[contains(@class, "wp-block-columns")]' );

		foreach ( $rows as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}
			// Troca: wp-block-columns -> block-columns
			$this->replace_class( $node, 'wp-block-columns', 'block-columns' );
		}

		// 2. Processa as Colunas (Itens).
		// Busca elementos que tenham a classe 'wp-block-column'.
		$cols = $xpath->query( '//*[contains(@class, "wp-block-column")]' );

		foreach ( $cols as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}
			// Troca: wp-block-column -> block-column
			$this->replace_class( $node, 'wp-block-column', 'block-column' );
		}
	}

	/**
	 * Helper para substituir uma classe CSS específica mantendo as outras.
	 *
	 * @param DOMElement $node      O elemento a ser modificado.
	 * @param string     $old       A classe antiga a remover.
	 * @param string     $new_class A nova classe a adicionar.
	 */
	private function replace_class( DOMElement $node, string $old, string $new_class ): void {
		$current_classes = $node->getAttribute( 'class' );
		$classes_array   = explode( ' ', $current_classes );

		// Remove a classe antiga do array.
		$classes_array = array_diff( $classes_array, array( $old ) );

		// Adiciona a nova classe se ela ainda não existir (evita duplicatas).
		if ( ! in_array( $new_class, $classes_array, true ) ) {
			$classes_array[] = $new_class;
		}

		// Reconstrói a string de classes e atualiza o atributo.
		$node->setAttribute( 'class', trim( implode( ' ', $classes_array ) ) );
	}
}