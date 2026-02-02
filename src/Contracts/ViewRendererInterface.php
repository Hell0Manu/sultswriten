<?php
/**
 * Interface de Renderização de Views.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

/**
 * Interface ViewRendererInterface.
 *
 * Responsável por carregar arquivos de template PHP (views),
 * isolando a lógica de apresentação da lógica de controle.
 */
interface ViewRendererInterface {
	/**
	 * Renderiza uma view.
	 *
	 * @since 0.1.0
	 * @param string $view_name Nome do arquivo da view (sem extensão).
	 * @param array  $data      Dados a serem extraídos para a view.
	 * @return void
	 */
	public function render( string $view_name, array $data = array() ): void;
}