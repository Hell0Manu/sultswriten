<?php
namespace Sults\Writen\Contracts;

use DOMDocument;
use DOMXPath;

interface DomTransformerInterface {
	/**
	 * Aplica transformações no DOM.
	 *
	 * @param DOMDocument $dom O documento sendo manipulado.
	 * @param DOMXPath $xpath O facilitador de queries XPath.
	 */
	public function transform( DOMDocument $dom, DOMXPath $xpath ): void;
}
