<?php
namespace Sults\Writen\Workflow\Export\Transformers;

use Sults\Writen\Contracts\DomTransformerInterface;
use Sults\Writen\Contracts\AttachmentProviderInterface;
use Sults\Writen\Contracts\ConfigProviderInterface;
use DOMDocument;
use DOMXPath;
use DOMElement;

class ImageTransformer implements DomTransformerInterface {

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
		$images   = $xpath->query( '//img' );
		$home_url = $this->config->get_home_url();

		foreach ( $images as $img ) {
			if ( ! $img instanceof DOMElement ) {
				continue;
			}

			$src = trim( $img->getAttribute( 'src' ) );
			if ( ! $src ) {
				continue;
			}

			if ( stripos( $src, '.svg' ) !== false || strpos( $src, $home_url ) === false ) {
				continue;
			}

			if ( ! $img->hasAttribute( 'alt' ) || trim( $img->getAttribute( 'alt' ) ) === '' ) {
				$headings = $xpath->query(
					'preceding::h1[1] | preceding::h2[1] | preceding::h3[1] | preceding::h4[1] | preceding::h5[1] | preceding::h6[1]',
					$img
				);
				$alt_text = ( $headings->length > 0 && $headings->item( 0 ) )
					? trim( $headings->item( 0 )->textContent )
					: 'Imagem SULTS';
				$img->setAttribute( 'alt', $alt_text );
			}

			if ( ! $img->hasAttribute( 'title' ) ) {
				$img->setAttribute( 'title', $img->getAttribute( 'alt' ) );
			}

			if ( ! $img->hasAttribute( 'loading' ) ) {
				$img->setAttribute( 'loading', 'lazy' );
			}

			$attachment_id = $this->attachment_provider->get_attachment_id_by_url( $src );
			if ( $attachment_id ) {
				$image_data = $this->attachment_provider->get_image_src( $attachment_id, 'full' );
				if ( $image_data ) {
					if ( ! $img->hasAttribute( 'width' ) ) {
						$img->setAttribute( 'width', (string) $image_data[1] );
					}
					if ( ! $img->hasAttribute( 'height' ) ) {
						$img->setAttribute( 'height', (string) $image_data[2] );
					}
				}
			}
		}
	}
}
