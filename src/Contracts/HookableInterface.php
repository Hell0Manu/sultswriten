<?php
/**
 * Registra os hooks (actions e filters) do serviço no WordPress.
 */

namespace Sults\Writen\Contracts;

interface HookableInterface {
	public function register(): void;
}
