<?php
namespace Sults\Writen\Workflow\Export\Transformers;

use Sults\Writen\Contracts\DomTransformerInterface;
use Sults\Writen\Contracts\ConfigProviderInterface;
use DOMDocument;
use DOMXPath;
use DOMElement;

class LinkTransformer implements DomTransformerInterface {

	private ConfigProviderInterface $config;

	public function __construct( ConfigProviderInterface $config ) {
		$this->config = $config;
	}

	public function transform( DOMDocument $dom, DOMXPath $xpath ): void {
		$links           = $xpath->query( '//a' );
		$internal_domain = $this->config->get_internal_domain();

		foreach ( $links as $link ) {
			if ( ! $link instanceof DOMElement ) {
				continue;
			}

			$href = trim( $link->getAttribute( 'href' ) );
			if ( ! $href ) {
				continue;
			}

			if ( preg_match( '/^https?:\/\//i', $href ) && strpos( $href, $internal_domain ) === false ) {

				if ( ! $link->hasAttribute( 'target' ) ) {
					$link->setAttribute( 'target', '_blank' );
				}

				$rel   = $link->getAttribute( 'rel' );
				$parts = $rel ? explode( ' ', $rel ) : array();

				$changed = false;
				if ( ! in_array( 'noopener', $parts, true ) ) {
					$parts[] = 'noopener';
					$changed = true;
				}
				if ( ! in_array( 'noreferrer', $parts, true ) ) {
					$parts[] = 'noreferrer';
					$changed = true;
				}

				if ( $changed ) {
					$link->setAttribute( 'rel', trim( implode( ' ', $parts ) ) );
				}
			}
		}
	}
}
