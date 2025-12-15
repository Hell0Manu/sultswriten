<?php
namespace Sults\Writen\Workflow\Export\Transformers;

use Sults\Writen\Contracts\DomTransformerInterface;
use DOMDocument;
use DOMXPath;
use DOMElement;

class GridTransformer implements DomTransformerInterface {

	public function transform( DOMDocument $dom, DOMXPath $xpath ): void {
		$rows = $xpath->query( '//*[contains(@class, "wp-block-columns")]' );

		foreach ( $rows as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}

			$this->replace_class( $node, 'wp-block-columns', 'block-columns' );
		}

		$cols = $xpath->query( '//*[contains(@class, "wp-block-column")]' );

		foreach ( $cols as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}
			$this->replace_class( $node, 'wp-block-column', 'block-column' );
		}
	}

	private function replace_class( DOMElement $node, string $old, string $new_class ): void {
		$current_classes = $node->getAttribute( 'class' );
		$classes_array   = explode( ' ', $current_classes );

		$classes_array = array_diff( $classes_array, array( $old ) );

		if ( ! in_array( $new_class, $classes_array, true ) ) {
			$classes_array[] = $new_class;
		}

		$node->setAttribute( 'class', trim( implode( ' ', $classes_array ) ) );
	}
}
