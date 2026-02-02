<?php
/**
 * Gerenciador de Assets de Exportação (Imagens).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Export;

use DOMDocument;
use DOMElement;

/**
 * Classe ExportAssetsManager.
 *
 * Responsável por gerenciar os recursos estáticos (principalmente imagens) durante a exportação.
 *
 * Realiza um pipeline de processamento em cada tag `<img>` encontrada no conteúdo:
 * 1. Resolução: Converte a URL pública da imagem em um caminho de arquivo físico no servidor.
 * 2. Renomeação: Aplica nomes amigáveis (SEO) baseados no atributo Title ou Alt da imagem.
 * 3. Mapeamento: Prepara a lista de arquivos para serem incluídos no ZIP.
 * 4. Substituição: Atualiza o atributo `src` no HTML para apontar para a estrutura do ZIP.
 * 5. Limpeza: Remove atributos específicos do WordPress (srcset, sizes) incompatíveis com o legado.
 *
 * @see ExportPayload
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export
 * @author     Sults
 * @since      0.1.0
 */
class ExportAssetsManager {

	/** @var string Caminho base de uploads do WP. */
	private string $base_upload_path;

	/** @var string URL base de uploads do WP. */
	private string $base_site_url;

	/**
	 * Construtor.
	 * Inicializa os caminhos base do diretório de uploads.
	 */
	public function __construct() {
		$upload_dir             = wp_upload_dir();
		$this->base_upload_path = $upload_dir['basedir'];
		$this->base_site_url    = $upload_dir['baseurl'];
	}

	/**
	 * Processa o HTML para extrair e reescrever referências a imagens.
	 *
	 * @since 0.1.0
	 *
	 * @param string $html              O HTML limpo do post.
	 * @param string $zip_folder_prefix O caminho relativo onde as imagens ficarão no ZIP (ex: "images").
	 * @return ExportPayload Objeto contendo o HTML modificado e o mapa de arquivos.
	 */
	public function process( string $html, string $zip_folder_prefix ): ExportPayload {
		if ( empty( $html ) ) {
			return new ExportPayload( '', array() );
		}

		$dom = new DOMDocument();
		// Suprime erros de parsing HTML5.
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

			// Se a imagem não for local ou não existir, ignora.
			if ( ! $local_path || ! file_exists( $local_path ) ) {
				continue;
			}

			// Lógica de Renomeação SEO: Title > Alt > Nome Original.
			$raw_name = $img->getAttribute( 'title' );
			if ( empty( $raw_name ) ) {
				$raw_name = $img->getAttribute( 'alt' );
			}
			if ( empty( $raw_name ) ) {
				$raw_name = pathinfo( $local_path, PATHINFO_FILENAME );
			}

			// Sanitiza o nome escolhido.
			$safe_name = $this->sanitize_filename( $raw_name );

			// Determina extensão.
			$ext_val   = pathinfo( $local_path, PATHINFO_EXTENSION );
			$extension = $ext_val ? $ext_val : 'jpg';

			// Tratamento de colisão de nomes (ex: imagem_1.jpg, imagem_2.jpg).
			$final_name = $safe_name . '.' . $extension;
			$counter    = 1;

			while ( isset( $used_filenames[ $final_name ] ) ) {
				$final_name = $safe_name . '_' . $counter . '.' . $extension;
				++$counter;
			}
			$used_filenames[ $final_name ] = true;

			// Define caminho no ZIP e adiciona ao mapa.
			$clean_prefix = trim( $zip_folder_prefix, '/' );
			$zip_path     = $clean_prefix . '/' . $final_name;

			$files_to_zip[ $local_path ] = $zip_path;
			
			// Atualiza o SRC no HTML para o caminho relativo do JSP.
			// Nota: Adiciona '/' no início para ser relativo à raiz do contexto se necessário,
			// ou ajuste conforme a necessidade do JSP.
			$img->setAttribute( 'src', '/' . $zip_path );

			// Limpeza de atributos incompatíveis com o legado.
			$img->removeAttribute( 'style' );
			$img->removeAttribute( 'srcset' ); // Remove imagens responsivas do WP.
			$img->removeAttribute( 'sizes' );
			$img->removeAttribute( 'class' );  // Remove classes de alinhamento/tamanho do WP.
		}

		$sults_processed_html = $dom->saveHTML();

		// Remove hacks de XML e decodifica entidades.
		$sults_processed_html = preg_replace( '/^<\?xml.+?\?>\s*/i', '', $sults_processed_html );
		$final_html           = html_entity_decode( $sults_processed_html, ENT_NOQUOTES, 'UTF-8' );

		return new ExportPayload( $final_html, $files_to_zip );
	}

	/**
	 * Tenta resolver uma URL pública para um caminho de arquivo local.
	 *
	 * Utiliza heurísticas para encontrar o arquivo dentro da pasta `wp-content`.
	 * Inclui verificações de segurança para evitar Directory Traversal.
	 *
	 * @param string $url A URL da imagem.
	 * @return string|null O caminho absoluto do arquivo ou null se não encontrado.
	 */
	private function resolve_local_path( string $url ): ?string {
		$url = urldecode( $url );
		$url = str_replace( '\\', '/', $url );

		// Tenta quebrar a URL a partir de 'wp-content'.
		$sults_parts = explode( 'wp-content', $url );
		if ( count( $sults_parts ) < 2 ) {
			return null;
		}
		$relative_to_wpcontent = $sults_parts[1];

		// Tentativa 1: Caminho padrão via WP_CONTENT_DIR.
		$local_path = WP_CONTENT_DIR . $relative_to_wpcontent;

		// Tentativa 2: Se falhar, tenta reconstruir via base de uploads (caso de configurações exóticas).
		if ( ! file_exists( $local_path ) ) {
			$upload_dir     = wp_upload_dir();
			$relative_clean = preg_replace( '|^[\\\\/]?uploads|', '', $relative_to_wpcontent );
			$local_path     = rtrim( $upload_dir['basedir'], '/' ) . '/' . ltrim( $relative_clean, '/' );
		}

		$real_path = realpath( $local_path );

		if ( ! $real_path || ! file_exists( $real_path ) ) {
			return null;
		}

		// Trava de Segurança: Garante que o arquivo resolvido está realmente dentro de wp-content.
		$safe_path_root = wp_normalize_path( realpath( WP_CONTENT_DIR ) );
		$safe_file_path = wp_normalize_path( $real_path );

		if ( strpos( $safe_file_path, $safe_path_root ) !== 0 ) {
			return null; // Tentativa de acesso fora do diretório permitido.
		}

		return $real_path;
	}

	/**
	 * Sanitiza nomes de arquivos.
	 *
	 * Converte para snake_case, remove acentos e limita o tamanho.
	 *
	 * @param string $text Nome bruto.
	 * @return string Nome seguro.
	 */
	private function sanitize_filename( string $text ): string {
		$text = remove_accents( $text );
		$text = strtolower( $text );
		$text = preg_replace( '/[^a-z0-9]+/', '_', $text ); // Substitui tudo que não for alfanumérico por _.
		$text = trim( $text, '_' );

		if ( strlen( $text ) > 60 ) {
			$text = substr( $text, 0, 60 );
			$text = rtrim( $text, '_' );
		}

		return $text ? $text : 'imagem';
	}
}
