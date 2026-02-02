<?php
/**
 * Interface de Provedor de Anexos (Attachments).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

/**
 * Interface AttachmentProviderInterface.
 *
 * Abstrai a recuperação de metadados de arquivos de mídia da biblioteca do WordPress.
 * Útil para converter URLs de imagens em caminhos locais ou IDs de anexo.
 */
interface AttachmentProviderInterface {
	/**
	 * Tenta encontrar o ID de um anexo a partir da sua URL.
	 *
	 * @since 0.1.0
	 * @param string $url A URL completa da imagem.
	 * @return int O ID do anexo ou 0 se não encontrado.
	 */
	public function get_attachment_id_by_url( string $url ): int;

	/**
	 * Recupera os dados da imagem (src, width, height) para um tamanho específico.
	 *
	 * @since 0.1.0
	 * @param int    $attachment_id ID do anexo.
	 * @param string $size          Tamanho registrado (ex: 'thumbnail', 'full').
	 * @return array|null Array com [url, width, height] ou null se falhar.
	 */
	public function get_image_src( int $attachment_id, string $size = 'full' ): ?array;

	/**
	 * Retorna apenas a URL do arquivo original de um anexo.
	 *
	 * @since 0.1.0
	 * @param int $attachment_id ID do anexo.
	 * @return string|null URL ou null.
	 */
	public function get_attachment_url( int $attachment_id ): ?string;
}