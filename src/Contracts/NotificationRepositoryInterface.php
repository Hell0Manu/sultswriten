<?php
/**
 * Contrato para o repositório de persistência de notificações.
 *
 * Garante que a lógica de armazenamento (user_meta, banco de dados, etc.)
 * seja desacoplada da lógica de negócios (NotificationManager).
 */

namespace Sults\Writen\Contracts;

interface NotificationRepositoryInterface {

	public function add_notification( int $user_id, array $notification ): bool;

	public function get_notifications( int $user_id ): array;

	public function dismiss_notification( int $user_id, string $notification_id ): bool;

	public function clear_all_notifications( int $user_id ): bool;
}
