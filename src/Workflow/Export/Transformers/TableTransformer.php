<?php
namespace Sults\Writen\Workflow\Export\Transformers;

use Sults\Writen\Contracts\DomTransformerInterface;
use DOMDocument;
use DOMXPath;

class TableTransformer implements DomTransformerInterface {
	public function transform( DOMDocument $dom, DOMXPath $xpath ): void {
		$tables = $xpath->query( '//table' );

		foreach ( $tables as $table ) {
			$parent = $table->parentNode;
			if ( $parent && $parent->nodeName === 'div' && $parent->getAttribute( 'class' ) === 'table-content' ) {
				continue;
			}

			$wrapper = $dom->createElement( 'div' );
			$wrapper->setAttribute( 'class', 'table-content' );

			$parent->replaceChild( $wrapper, $table );
			$wrapper->appendChild( $table );
		}
	}
}
