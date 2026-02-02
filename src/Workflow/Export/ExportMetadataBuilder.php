<?php
/**
 * Construtor de Metadados de Exportação.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Export;

use WP_Post;
use Sults\Writen\Utils\PathHelper;

/**
 * Classe ExportMetadataBuilder.
 *
 * Responsável por gerar o arquivo auxiliar (geralmente `info.txt` ou `info.json`)
 * incluído no ZIP de exportação.
 *
 * Este arquivo contém instruções técnicas para o servidor de destino (Java/Tomcat), incluindo:
 * 1. Regras de `UrlRewriteFilter`: Mapeamento da URL amigável para o caminho físico do JSP.
 * 2. Entradas de Sitemap: XML para atualizar o sitemap do site principal.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export
 * @author     Sults
 * @since      0.1.0
 */
class ExportMetadataBuilder {

	/**
	 * Gera o conteúdo do arquivo de informações.
	 *
	 * Calcula os caminhos finais de produção, aplica regras de negócio específicas
	 * (como a injeção de pastas intermediárias) e formata a saída em blocos XML.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Post $sults_post      O post objeto da exportação.
	 * @param string  $target_filename O nome final do arquivo normalizado (ex: slug com hífens).
	 * @return string Conteúdo textual formatado contendo as regras de rewrite e sitemap.
	 */
	public function build_info_file( WP_Post $sults_post, string $target_filename = '' ): string {

		// Obtém o caminho base baseado na hierarquia (ex: /checklist/categoria/post).
		$raw_path   = PathHelper::get_relative_path( $sults_post->ID );
		$sults_path = rtrim( $raw_path, '/' );

		// Constantes do ambiente de produção.
		$domain_prod = 'https://www.sults.com.br';
		$base_jsp    = '/sults/pages/produtos';
		
		// --- LÓGICA DE MANIPULAÇÃO DE CAMINHO ---
		// Input:  /checklist/categoria/post
		// Output: /checklist/artigos/categoria/post
		$sults_parts = explode( '/', ltrim( $sults_path, '/' ) );
		$module      = $sults_parts[0] ?? '';

		$final_path = $sults_path;

		// Regra de Negócio: Módulo 'checklist' exige subpasta 'artigos'.
		if ( 'checklist' === $module && count( $sults_parts ) > 1 ) {
			$suffix     = substr( $sults_path, strlen( '/' . $module ) );
			$final_path = '/' . $module . '/artigos' . $suffix;
		}

		// --- AJUSTE DE NOME DE ARQUIVO ---
		// Se um nome de arquivo específico foi passado (ex: normalização de _ para -),
		// substituímos o slug original no caminho.

		if ( ! empty( $target_filename ) ) {
			// Ajuste para o caminho do JSP (File System).
			$path_parts = explode( '/', rtrim( $final_path, '/' ) );
			array_pop( $path_parts ); 
			$path_parts[] = $target_filename; 
			$final_path_for_jsp = implode( '/', $path_parts );
		} else {
			$final_path_for_jsp = $final_path;
		}

		if ( ! empty( $target_filename ) ) {
			// Ajuste para o caminho da URL (Browser/Sitemap).
			// Nota: Se a URL pública deve ser diferente do arquivo físico, ajuste aqui.
			// Atualmente, assume-se que URL == Nome do Arquivo.
			$loc_parts = explode( '/', rtrim( $sults_path, '/' ) ); 
			array_pop( $loc_parts );
			$loc_parts[] = $target_filename; 
			$final_path_for_loc = implode( '/', $loc_parts );
		} else {
			$final_path_for_loc = $sults_path;
		}

		// --- MONTAGEM DAS REGRAS ---
		// Regra 1: UrlRewrite (Entrada do usuário -> Arquivo físico JSP).
		$rewrite_from = '^' . $sults_path . '$'; // Regex exata.
		$rewrite_to   = $base_jsp . $final_path_for_jsp . '.jsp';

		// Regra 2: Sitemap (URL Pública).
		// Usa o caminho calculado com 'artigos' se aplicável.
		$sitemap_loc = $domain_prod . $final_path_for_jsp; 
		$last_mod    = get_the_modified_date( 'Y-m-d', $sults_post );

		// --- GERAÇÃO DO OUTPUT ---
		$content = '';

		$content .= "=== URLREWRITE ===\n";
		$content .= "<rule>\n";
		$content .= "    <from>{$rewrite_from}</from>\n";
		$content .= "    <to>{$rewrite_to}</to>\n";
		$content .= "</rule>\n\n";

		$content .= "=== SITEMAP ===\n";
		$content .= "<url>\n";
		$content .= "    <loc>{$sitemap_loc}</loc>\n";
		$content .= "    <lastmod>{$last_mod}</lastmod>\n";
		$content .= '</url>';

		return $content;
	}
}
