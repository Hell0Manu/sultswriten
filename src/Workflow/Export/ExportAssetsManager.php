<?php
namespace Sults\Writen\Workflow\Export;

use DOMDocument;
use DOMElement;

class ExportAssetsManager {

	private string $base_upload_path;
	private string $base_site_url;

	public function __construct() {
		$upload_dir             = wp_upload_dir();
		$this->base_upload_path = $upload_dir['basedir'];
		$this->base_site_url    = $upload_dir['baseurl'];
	}

	/**
	 * Processa o HTML: renomeia imagens baseadas no Title e ajusta caminhos para o ZIP.
	 *
	 * @param string $html O HTML limpo.
	 * @param string $zip_folder_prefix O caminho da pasta dentro do ZIP.
	 */
	public function process( string $html, string $zip_folder_prefix ): ExportPayload {
		if ( empty( $html ) ) {
			return new ExportPayload( '', array() );
		}

		$dom = new DOMDocument();
		libxml_use_internal_errors( true );

		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();

		$images         = $dom->getElementsByTagName( 'img' );
		$files_to_zip   = array();
		$used_filenames = array();

		foreach ( $images as $img ) {
			if ( ! $img instanceof DOMElement ) {
				continue;
			}

			$src        = $img->getAttribute( 'src' );
			$local_path = $this->resolve_local_path( $src );

			if ( ! $local_path || ! file_exists( $local_path ) ) {
				continue;
			}

			$raw_name = $img->getAttribute( 'title' );
			if ( empty( $raw_name ) ) {
				$raw_name = $img->getAttribute( 'alt' );
			}
			if ( empty( $raw_name ) ) {
				$raw_name = pathinfo( $local_path, PATHINFO_FILENAME );
			}

			$safe_name = $this->sanitize_filename( $raw_name );

			$ext_val   = pathinfo( $local_path, PATHINFO_EXTENSION );
			$extension = $ext_val ? $ext_val : 'jpg';

			$final_name = $safe_name . '.' . $extension;
			$counter    = 1;

			while ( isset( $used_filenames[ $final_name ] ) ) {
				$final_name = $safe_name . '_' . $counter . '.' . $extension;
				++$counter;
			}
			$used_filenames[ $final_name ] = true;

			$clean_prefix = trim( $zip_folder_prefix, '/' );
			$zip_path     = $clean_prefix . '/' . $final_name;

			$files_to_zip[ $local_path ] = $zip_path;
			$img->setAttribute( 'src', '/' . $zip_path );

			$img->removeAttribute( 'style' );
			$img->removeAttribute( 'srcset' );
			$img->removeAttribute( 'sizes' );
			$img->removeAttribute( 'class' );
		}

		$processed_html = $dom->saveHTML();

		$processed_html = preg_replace( '/^<\?xml.+?\?>\s*/i', '', $processed_html );
		$final_html     = html_entity_decode( $processed_html, ENT_NOQUOTES, 'UTF-8' );

		return new ExportPayload( $final_html, $files_to_zip );
	}

	private function resolve_local_path( string $url ): ?string {
		$url = urldecode( $url );
		if ( strpos( $url, $this->base_site_url ) === false ) {
			return null;
		}
		return str_replace( $this->base_site_url, $this->base_upload_path, $url );
	}

	private function sanitize_filename( string $text ): string {
		$text = remove_accents( $text );
		$text = strtolower( $text );
		$text = preg_replace( '/[^a-z0-9]+/', '_', $text );
		$text = trim( $text, '_' );

		if ( strlen( $text ) > 60 ) {
			$text = substr( $text, 0, 60 );
			$text = rtrim( $text, '_' );
		}

		return $text ? $text : 'imagem';
	}
}
