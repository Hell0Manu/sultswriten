<?php
/**
 * Interface de Transformador DOM.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

use DOMDocument;
use DOMXPath;

/**
 * Interface DomTransformerInterface.
 *
 * Contrato para classes que modificam a estrutura do HTML diretamente no DOM.
 * Utilizado na pipeline de exportação para alterar links, imagens e tabelas.
 */
interface DomTransformerInterface {
	/**
	 * Aplica transformações no documento DOM.
	 *
	 * @since 0.1.0
	 * @param DOMDocument $dom   O documento HTML completo sendo processado.
	 * @param DOMXPath    $xpath Facilitador para buscas complexas no DOM.
	 * @return void As alterações são feitas por referência no objeto $dom.
	 */
	public function transform( DOMDocument $dom, DOMXPath $xpath ): void;
}