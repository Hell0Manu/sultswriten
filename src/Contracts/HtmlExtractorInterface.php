<?php
namespace Sults\Writen\Contracts;

use WP_Post;

interface HtmlExtractorInterface {
	/**
	 * Processa o post: extrai o conteúdo, limpa o HTML e aplica melhorias estruturais.
	 *
	 * @param WP_Post $post O objeto do post.
	 * @return string O HTML pronto para ser inserido no JSP.
	 */
	public function extract( WP_Post $post ): string;
}
