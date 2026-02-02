<?php
/**
 * Interface Hookable.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */
namespace Sults\Writen\Contracts;

/**
 * Interface HookableInterface.
 *
 * Contrato para classes que precisam registrar ganchos (actions/filters) no WordPress.
 * Classes que implementam esta interface são detectadas automaticamente pelo `HookManager`.
 */
interface HookableInterface {
	/**
	 * Registra os hooks do serviço.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void;
}
