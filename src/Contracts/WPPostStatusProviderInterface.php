<?php
/**
 * Interface para o provedor de status de post.
 *
 * Define os métodos necessários para registrar e recuperar informações
 * sobre status de posts no WordPress.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

interface WPPostStatusProviderInterface {
	public function register( string $sults_post_type, array $args ): object;
	public function get_status( int $sults_post_id ): string;
	public function get_status_object( string $slug ): ?object;

	/**
	 * Retorna todos os slugs de status registrados (não internos).
	 *
	 * @return array
	 */
	public function get_all_status_slugs(): array;
}
