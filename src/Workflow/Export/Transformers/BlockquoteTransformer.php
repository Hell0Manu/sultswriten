<?php
namespace Sults\Writen\Workflow\Export\Transformers;

use Sults\Writen\Contracts\DomTransformerInterface;
use DOMDocument;
use DOMXPath;
use DOMElement;

class BlockquoteTransformer implements DomTransformerInterface {
	public function transform( DOMDocument $dom, DOMXPath $xpath ): void {
		$quotes = $xpath->query( '//blockquote' );

		foreach ( $quotes as $quote ) {
			if ( ! $quote instanceof DOMElement ) {
				continue;
			}

			$cites = $quote->getElementsByTagName( 'cite' );
			if ( $cites->length === 0 ) {
				continue;
			}

			$old_cite = $cites->item( 0 );
			if ( ! $old_cite ) {
				continue;
			}

			$full_text = rtrim( trim( $old_cite->textContent ), ' .' );

			if ( $old_cite->parentNode ) {
				$old_cite->parentNode->removeChild( $old_cite );
			}

			$footer      = $dom->createElement( 'footer' );
			$sults_parts = explode( ',', $full_text, 2 );
			$name        = trim( $sults_parts[0] );

			$footer->appendChild( $dom->createTextNode( "—{$name}" ) );

			if ( isset( $sults_parts[1] ) ) {
				$footer->appendChild( $dom->createTextNode( ', ' ) );
				$role_cite = $dom->createElement( 'cite', trim( $sults_parts[1] ) );
				$footer->appendChild( $role_cite );
			}
			$footer->appendChild( $dom->createTextNode( '.' ) );
			$quote->appendChild( $footer );
		}
	}
}
