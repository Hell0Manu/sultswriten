<?php
/**
 * Interface de Sanitização de Conteúdo.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

/**
 * Interface ContentSanitizerInterface.
 *
 * Define o contrato para serviços que limpam strings brutas,
 * removendo tags perigosas ou formatando caracteres especiais.
 */
interface ContentSanitizerInterface {
	/**
	 * Limpa e normaliza o conteúdo.
	 *
	 * @since 0.1.0
	 * @param string $html O conteúdo bruto.
	 * @return string O conteúdo higienizado.
	 */
	public function sanitize( string $html ): string;
}