<?php

	/**
	 * Atualiza as regras de reescrita de URL do WordPress.
	 * Deve ser chamado apenas na ativação/desativação, pois é custoso.
	 */

namespace Sults\Writen\Infrastructure;

class RewriteManager {
	public function flush(): void {
		flush_rewrite_rules();
	}
}
