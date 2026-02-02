<?php
/**
 * Interface do Extrator de HTML.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

use WP_Post;

/**
 * Interface HtmlExtractorInterface.
 *
 * Responsável pela primeira etapa da exportação: extrair o conteúdo bruto do post
 * e aplicar a cadeia de transformadores (Transformers) para limpar o HTML.
 */
interface HtmlExtractorInterface {
	/**
	 * Extrai e processa o HTML de um post.
	 *
	 * @since 0.1.0
	 * @param WP_Post $sults_post O objeto do post.
	 * @return string O HTML limpo e transformado.
	 */
	public function extract( WP_Post $sults_post ): string;
}