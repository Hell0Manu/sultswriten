<?php
/**
 * Interface para provedor de status de post.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

/**
 * Interface WPUserProviderInterface.
 *
 * Abstrai o acesso às funções de usuário do WordPress (wp_get_current_user, get_user_meta, etc.),
 * permitindo que o sistema seja testável e não dependa diretamente do estado global do WP.
 */
interface WPUserProviderInterface {
	/**
	 * Retorna as roles (papéis) do usuário atual.
	 *
	 * @since 0.1.0
	 * @return array<string> Lista de roles (ex: ['administrator', 'editor']).
	 */
	public function get_current_user_roles(): array;

	/**
	 * Gera o HTML de um dropdown de usuários.
	 *
	 * @since 0.1.0
	 * @param array $args Argumentos padrão do wp_dropdown_users.
	 * @return string O HTML do select.
	 */
	public function get_users_dropdown( array $args ): string;

	/**
	 * Retorna o ID do usuário logado.
	 *
	 * @since 0.1.0
	 * @return int O ID do usuário ou 0 se não logado.
	 */
	public function get_current_user_id(): int;

	/**
	 * Recupera metadados de um usuário.
	 *
	 * @since 0.1.0
	 * @param int    $user_id ID do usuário.
	 * @param string $key     Chave do metadado.
	 * @param bool   $single  Se deve retornar valor único ou array.
	 * @return mixed Valor do metadado.
	 */
	public function get_user_meta( int $user_id, string $key, bool $single = false );

	/**
	 * Atualiza um metadado de usuário.
	 *
	 * @since 0.1.0
	 * @param int    $user_id ID do usuário.
	 * @param string $key     Chave do metadado.
	 * @param mixed  $value   Novo valor.
	 * @return int|bool ID do meta se criado, true se atualizado, false se falha.
	 */
	public function update_user_meta( int $user_id, string $key, $value );

/**
	 * Verifica se o usuário atual tem uma capacidade específica.
	 *
	 * @since 0.1.0
	 * @param string $capability A capacidade a verificar.
	 * @param mixed  ...$args    Argumentos adicionais (ex: ID do post).
	 * @return bool True se permitido.
	 */
	public function current_user_can( string $capability, ...$args ): bool;
}
