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

			$link->removeAttribute( 'title' );

			$href = trim( $link->getAttribute( 'href' ) );
			if ( ! $href ) {
				continue;
			}

			if ( strpos( $href, '#' ) === 0 || stripos( $href, 'mailto:' ) === 0 || stripos( $href, 'tel:' ) === 0 ) {
				continue;
			}

			if ( ! $link->hasAttribute( 'target' ) ) {
				$link->setAttribute( 'target', '_blank' );
			}

			$parsed_url = wp_parse_url( $href );
			$host       = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';

			$is_internal = empty( $host ) || ( stripos( $host, $internal_domain ) !== false );

			if ( ! $is_internal ) {
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
