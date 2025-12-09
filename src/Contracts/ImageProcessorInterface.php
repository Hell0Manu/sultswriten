<?php
namespace Sults\Writen\Contracts;

interface ImageProcessorInterface {
	/**
	 * Processa um arquivo de imagem (ex: redimensiona e converte).
	 *
	 * @param array $upload O array padrão do WordPress contendo 'file', 'url', 'type'.
	 * @return array O array modificado.
	 */
	public function process( array $upload ): array;
}
