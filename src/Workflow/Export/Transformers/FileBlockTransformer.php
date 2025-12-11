<?php
namespace Sults\Writen\Workflow\Export\Transformers;

use Sults\Writen\Contracts\DomTransformerInterface;
use Sults\Writen\Contracts\AttachmentProviderInterface;
use Sults\Writen\Contracts\ConfigProviderInterface;
use DOMDocument;
use DOMXPath;

class FileBlockTransformer implements DomTransformerInterface {

	private AttachmentProviderInterface $attachment_provider;
	private ConfigProviderInterface $config;

	public function __construct(
		AttachmentProviderInterface $attachment_provider,
		ConfigProviderInterface $config
	) {
		$this->attachment_provider = $attachment_provider;
		$this->config              = $config;
	}

	public function transform( DOMDocument $dom, DOMXPath $xpath ): void {
		$nodes     = $xpath->query( '//div[contains(@class, "wp-block-file")]' );
		$base_path = $this->config->get_downloads_base_path();

		foreach ( $nodes as $node ) {
			$links = $node->getElementsByTagName( 'a' );
			if ( $links->length === 0 ) {
				continue;
			}

			$best_link = $links->item( 0 );
			foreach ( $links as $link ) {
				if ( $link->hasAttribute( 'download' ) ) {
					$best_link = $link;
					break;
				}
			}

			$href     = $best_link->getAttribute( 'href' );
			$filename = basename( $href );

			if ( strpos( $filename, '.' ) === false ) {
				$att_id = $this->attachment_provider->get_attachment_id_by_url( $href );

				if ( $att_id ) {
					$real_url = $this->attachment_provider->get_attachment_url( $att_id );
					if ( $real_url ) {
						$filename = basename( $real_url );
					}
				}
			}

			if ( strpos( $filename, '.' ) === false ) {
				$filename = 'arquivo-download';
			}

			$final_href = $base_path . $filename;
			$text       = trim( $best_link->textContent );
			if ( empty( $text ) || strtolower( $text ) === 'baixar' ) {
				$text = $filename;
			}

			$btn = $dom->createElement( 'a' );
			$btn->setAttribute( 'href', $final_href );
			$btn->setAttribute( 'download', '' );
			$btn->setAttribute( 'target', '_blank' );
			$btn->setAttribute( 'rel', 'noopener noreferrer' );
			$btn->setAttribute( 'class', 'btn btn-rounded call-to-action-btn primary' );
			$btn->setAttribute( 'style', 'background: linear-gradient(-20deg, #00acac 0, #00acac 100%); color: #ffffff; border: solid 2px #00acac; padding: 10px 24px; text-transform: initial; font-size: 1rem;' );

			$btn->appendChild( $dom->createTextNode( $text . ' ' ) );

			$icon = $dom->createElement( 'img' );
			$icon->setAttribute( 'src', '/sults/images/icones/marca/download.svg' );
			$icon->setAttribute( 'width', '22' );
			$icon->setAttribute( 'height', '22' );
			$icon->setAttribute( 'style', 'margin-left:3px' );
			$icon->setAttribute( 'alt', 'Ícone Download' );

			$btn->appendChild( $icon );

			$node->parentNode->replaceChild( $btn, $node );
		}
	}
}
