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
        $upload_dir = wp_upload_dir();

        foreach ( $images as $img ) {
            if ( ! $img instanceof DOMElement ) {
                continue;
            }

            $src = trim( $img->getAttribute( 'src' ) );
            if ( ! $src ) {
                continue;
            }

            if ( stripos( $src, '.svg' ) !== false ) {
                continue;
            }

            $is_internal = ( strpos( $src, $home_url ) !== false ) || ( substr( $src, 0, 1 ) === '/' );

            if ( ! $is_internal ) {
                continue;
            }

            $headings = $xpath->query(
                'preceding::h2[1]',
                //'preceding::*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6][1]',
                $img
            );

            $alt_text = '';

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
            $img->setAttribute( 'title', $alt_text );

            if ( ! $img->hasAttribute( 'loading' ) ) {
                $img->setAttribute( 'loading', 'lazy' );
            }

            $original_width  = 0;
            $original_height = 0;

            $attachment_id = $this->attachment_provider->get_attachment_id_by_url( $src );
            
            if ( $attachment_id ) {
                $image_data = $this->attachment_provider->get_image_src( $attachment_id, 'full' );
                if ( $image_data ) {
                    $original_width  = (int) $image_data[1];
                    $original_height = (int) $image_data[2];
                }
            }

            if ( $original_width === 0 ) {
                $local_path = $this->resolve_local_path_fallback( $src, $upload_dir );
                
                if ( $local_path && file_exists( $local_path ) ) {
                    $size = @getimagesize( $local_path ); 
                    if ( $size ) {
                        $original_width  = $size[0];
                        $original_height = $size[1];
                    }
                }
            }

            if ( $original_width > 0 && $original_height > 0 ) {
                $target_width = 850;
                
                $target_height = round( ( $original_height / $original_width ) * $target_width );

                $img->setAttribute( 'width', (string) $target_width );
                $img->setAttribute( 'height', (string) $target_height );
            }
        }
    }

    private function resolve_local_path_fallback( string $url, array $upload_dir ): ?string {
        $url = urldecode( $url );
        $path = parse_url( $url, PHP_URL_PATH );
        
        if ( empty( $path ) ) {
            return null;
        }

        if ( strpos( $path, '/wp-content/uploads' ) !== false ) {
            $parts = explode( '/wp-content/uploads', $path );
            if ( isset( $parts[1] ) ) {
                return $upload_dir['basedir'] . $parts[1];
            }
        }
        
        if ( strpos( $path, '/wp-content' ) !== false ) {
             $parts = explode( '/wp-content', $path );
             if ( isset( $parts[1] ) ) {
                 return WP_CONTENT_DIR . $parts[1];
             }
        }

        return null;
    }
}