<?php
/**
 * Interface WPUserProviderInterface
 * * Contrato que define quais operações relacionadas a usuários o plugin precisa.
 * Serve para desacoplar o código das funções globais do WordPress.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

interface WPUserProviderInterface {
	public function get_current_user_roles(): array;
	public function get_users_dropdown( array $args ): string;
	public function get_current_user_id(): int;
	public function get_user_meta( int $user_id, string $key, bool $single = false );
	public function update_user_meta( int $user_id, string $key, $value );

	/**
     * Verifica permissões do usuário atual.
     * @param string $capability
     * @param mixed ...$args
     * @return bool
     */
    public function current_user_can( string $capability, ...$args ): bool;
}
