<?php
/**
 * Serviço de Nomenclatura de Arquivos de Exportação.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Export;

use Sults\Writen\Contracts\ExportNamingServiceInterface;

/**
 * Classe ExportNamingService.
 *
 * Responsável por gerar nomes de arquivos seguros e padronizados para os pacotes ZIP.
 *
 * Garante que o título do post seja convertido em um slug válido para o sistema de arquivos,
 * evitando caracteres especiais, espaços e nomes excessivamente longos que poderiam
 * causar erros no download ou na descompactação.
 *
 * @see ExportNamingServiceInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export
 * @author     Sults
 * @since      0.1.0
 */
class ExportNamingService implements ExportNamingServiceInterface {

	/**
	 * Gera um nome de arquivo seguro a partir de um título bruto.
	 *
	 * Processo:
	 * 1. Sanitiza o título (converte para slug: minúsculas, hífens, sem acentos).
	 * 2. Trunca para um limite máximo de 50 caracteres para evitar caminhos muito longos.
	 * 3. Remove hífens sobrando no final após o corte.
	 * 4. Aplica um fallback ('exportacao-sults') se o resultado for vazio.
	 *
	 * @since 0.1.0
	 *
	 * @param string $raw_title O título original do post (ex: "Como Configurar o Módulo X").
	 * @return string O nome base do arquivo formatado (ex: "como-configurar-o-modulo-x").
	 */
	public function generate_zip_filename( string $raw_title ): string {
		// Converte para slug URL-friendly (ex: "Olá Mundo" -> "ola-mundo").
		$base_name = sanitize_title( $raw_title );

		// Limite de segurança para compatibilidade com diversos SOs.
		$char_limit = 50;
		if ( strlen( $base_name ) > $char_limit ) {
			$base_name = substr( $base_name, 0, $char_limit );
			// Remove hífen "pendurado" se o corte ocorreu no meio de uma palavra separada.
			$base_name = rtrim( $base_name, '-' );
		}

		// Fallback para títulos vazios ou compostos apenas por caracteres inválidos.
		if ( empty( $base_name ) ) {
			$base_name = 'exportacao-sults';
		}

		return $base_name;
	}
}