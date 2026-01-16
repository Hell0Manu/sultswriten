<?php
namespace Sults\Writen\Workflow\Export\Transformers;

use Sults\Writen\Contracts\DomTransformerInterface;
use DOMDocument;
use DOMXPath;
use DOMElement;

class TableTransformer implements DomTransformerInterface {
	public function transform( DOMDocument $dom, DOMXPath $xpath ): void {
		$tables = $xpath->query( '//table' );

		foreach ( $tables as $table ) {
			$sults_parent = $table->parentNode;
			if ( $sults_parent instanceof DOMElement && $sults_parent->nodeName === 'div' && $sults_parent->getAttribute( 'class' ) === 'table-content' ) {
				continue;
			}

			$wrapper = $dom->createElement( 'div' );
			$wrapper->setAttribute( 'class', 'table-content' );

			if ( $sults_parent ) {
				$sults_parent->replaceChild( $wrapper, $table );
				$wrapper->appendChild( $table );
			}
		}
	}
}
