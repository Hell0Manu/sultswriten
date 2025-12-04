<?php
/**
 * Interface para o provedor de dados de usuário.
 *
 * Define o contrato para acessar informações do usuário atual e listar usuários,
 * isolando a lógica direta do WordPress.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

interface WPUserProviderInterface {
	public function get_current_user_roles(): array;

	public function get_users_dropdown( array $args ): string;
}