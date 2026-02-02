<?php
/**
 * Interface de Processamento de Imagem.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

/**
 * Interface ImageProcessorInterface.
 *
 * Define o contrato para manipulação de uploads de imagem,
 * como conversão para WebP, redimensionamento ou otimização.
 */
interface ImageProcessorInterface {
	/**
	 * Processa um arquivo de imagem após o upload.
	 *
	 * @since 0.1.0
	 * @param array $upload Array do WP contendo 'file', 'url', 'type'.
	 * @return array O array modificado após o processamento.
	 */
	public function process( array $upload ): array;
}