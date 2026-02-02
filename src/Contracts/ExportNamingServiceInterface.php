<?php
/**
 * Interface de Nomenclatura de Exportação.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

/**
 * Interface ExportNamingServiceInterface.
 *
 * Responsável por padronizar os nomes dos arquivos gerados (ZIPs),
 * garantindo que sejam amigáveis e contenham identificadores úteis (ex: slug, data).
 */
interface ExportNamingServiceInterface {
	/**
	 * Gera o nome do arquivo ZIP baseado no título do post.
	 *
	 * @since 0.1.0
	 * @param string $raw_title O título original do post.
	 * @return string O nome do arquivo formatado (ex: "checklist-limpeza.zip").
	 */
	public function generate_zip_filename( string $raw_title ): string;
}