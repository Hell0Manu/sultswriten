<?php
namespace Sults\Writen\Contracts;

interface ContentSanitizerInterface {
	/**
	 * Limpa e normaliza o conteúdo HTML bruto.
	 *
	 * @param string $html O conteúdo bruto do post.
	 * @return string O HTML higienizado.
	 */
	public function sanitize( string $html ): string;
}
