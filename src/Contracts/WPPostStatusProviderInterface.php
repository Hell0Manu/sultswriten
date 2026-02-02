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
 * Interface WPPostStatusProviderInterface.
 *
 * Gerencia o registro e a recuperação de status personalizados (Custom Post Status) no WordPress.
 */
interface WPPostStatusProviderInterface {
	/**
	 * Registra um novo status de post no WordPress.
	 *
	 * @since 0.1.0
	 * @param string $sults_post_type O slug do status (ex: 'em-revisao').
	 * @param array  $args            Argumentos de configuração (label, public, etc).
	 * @return object O objeto de status registrado.
	 */
	public function register( string $sults_post_type, array $args ): object;

	/**
	 * Recupera o slug do status atual de um post.
	 *
	 * @since 0.1.0
	 * @param int $sults_post_id ID do post.
	 * @return string Slug do status (ex: 'publish').
	 */
	public function get_status( int $sults_post_id ): string;

	/**
	 * Recupera o objeto de configuração de um status específico.
	 *
	 * @since 0.1.0
	 * @param string $slug O slug do status.
	 * @return object|null Objeto stdClass do status ou null.
	 */
	public function get_status_object( string $slug ): ?object;

	/**
	 * Retorna todos os slugs de status registrados pelo plugin.
	 *
	 * @since 0.1.0
	 * @return array<string>
	 */
	public function get_all_status_slugs(): array;
}
