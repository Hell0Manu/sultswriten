<?php
namespace Sults\Writen\Workflow\Export\Transformers;

use Sults\Writen\Contracts\DomTransformerInterface;
use Sults\Writen\Contracts\ConfigProviderInterface;
use DOMDocument;
use DOMXPath;

class SultsTipTransformer implements DomTransformerInterface {

	private ConfigProviderInterface $config;

	public function __construct( ConfigProviderInterface $config ) {
		$this->config = $config;
	}

	public function transform( DOMDocument $dom, DOMXPath $xpath ): void {
		$nodes = $xpath->query( '//pre' );

		foreach ( $nodes as $node ) {
			$aside = $dom->createElement( 'aside' );
			$aside->setAttribute( 'class', 'dica-sults' );

			$img = $dom->createElement( 'img' );

			$icon_path = $this->config->get_tips_icon_path();
			$img->setAttribute( 'src', $icon_path );
			$img->setAttribute( 'alt', 'Dica Sults' );
			$aside->appendChild( $img );

			$div = $dom->createElement( 'div' );
			$h3  = $dom->createElement( 'h3', 'Dica Sults' );
			$div->appendChild( $h3 );

			$p = $dom->createElement( 'p' );
			$p->appendChild( $dom->createTextNode( trim( $node->nodeValue ) ) );
			$div->appendChild( $p );

			$aside->appendChild( $div );

			if ( $node->parentNode ) {
				$node->parentNode->replaceChild( $aside, $node );
			}
		}
	}
}
