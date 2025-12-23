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

				if ( $headings->length > 0 && $headings->item( 0 ) ) {
					$alt_text = trim( $headings->item( 0 )->textContent );
				} else {
					$post_id    = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
					$post_title = $post_id ? get_the_title( $post_id ) : '';

					if ( ! empty( $post_title ) && stripos( $post_title, 'untitled' ) === false ) {
						$alt_text = $post_title;
					} else {
						$alt_text = pathinfo( parse_url( $src, PHP_URL_PATH ), PATHINFO_FILENAME );
					}
				}

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
