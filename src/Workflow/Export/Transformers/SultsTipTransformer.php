<?php
namespace Sults\Writen\Workflow\Export\Transformers;

use Sults\Writen\Contracts\DomTransformerInterface;
use Sults\Writen\Contracts\ConfigProviderInterface;
use DOMDocument;
use DOMXPath;
use DOMNode;

class SultsTipTransformer implements DomTransformerInterface {

	private ConfigProviderInterface $config;

	public function __construct( ConfigProviderInterface $config ) {
		$this->config = $config;
	}

	public function transform( DOMDocument $dom, DOMXPath $xpath ): void {
		$pre_nodes = $xpath->query( '//pre' );
		foreach ( $pre_nodes as $node ) {
			$this->replace_with_tip( $dom, $node, $node->nodeValue );
		}

		$tables = $xpath->query( '//table' );
		foreach ( $tables as $table ) {
			if ( stripos( $table->textContent, 'Dica SULTS' ) !== false ) {

				$clean_text = str_ireplace( array( 'Dica SULTS:', 'Dica SULTS' ), '', $table->textContent );
				$clean_text = trim( $clean_text );

				$this->replace_with_tip( $dom, $table, $clean_text );
			}
		}
	}


	private function replace_with_tip( DOMDocument $dom, DOMNode $target_node, string $text_content ): void {
		$aside = $dom->createElement( 'aside' );
		$aside->setAttribute( 'class', 'dica-sults' );

		$img       = $dom->createElement( 'img' );
		$icon_path = $this->config->get_tips_icon_path();

		if ( empty( $icon_path ) ) {
			$icon_path = SULTSWRITEN_TIPS_ICON;
		}

		$img->setAttribute( 'src', $icon_path );
		$img->setAttribute( 'alt', 'Dica SULTS' );
		$aside->appendChild( $img );

		$div = $dom->createElement( 'div' );

		$h3 = $dom->createElement( 'h3', 'Dica SULTS' );
		$div->appendChild( $h3 );

		$p = $dom->createElement( 'p' );
		$p->appendChild( $dom->createTextNode( $text_content ) );
		$div->appendChild( $p );

		$aside->appendChild( $div );

		if ( $target_node->parentNode ) {
			$target_node->parentNode->replaceChild( $aside, $target_node );
		}
	}
}
