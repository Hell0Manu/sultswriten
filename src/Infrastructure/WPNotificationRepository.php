<?php
/**
 * Implementação do repositório de notificações usando user_meta do WordPress.
 */

namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\NotificationRepositoryInterface;

class WPNotificationRepository implements NotificationRepositoryInterface {

	private const META_KEY          = '_sults_user_notifications';
	private const MAX_NOTIFICATIONS = 20;

	/**
	 * Adiciona uma nova notificação à lista de um usuário.
	 *
	 * @param int $user_id ID do usuário.
	 * @param array $notification Dados da notificação.
	 * @return bool
	 */
	public function add_notification( int $user_id, array $notification ): bool {
		$user_notifs = $this->get_notifications( $user_id );

		array_unshift( $user_notifs, $notification );
		$user_notifs = array_slice( $user_notifs, 0, self::MAX_NOTIFICATIONS );

		return update_user_meta( $user_id, self::META_KEY, $user_notifs );
	}

	/**
	 * Obtém todas as notificações de um usuário.
	 *
	 * @param int $user_id ID do usuário.
	 * @return array
	 */
	public function get_notifications( int $user_id ): array {
		$user_notifs = get_user_meta( $user_id, self::META_KEY, true );

		if ( ! is_array( $user_notifs ) ) {
			return array();
		}

		return $user_notifs;
	}

	/**
	 * Remove uma notificação específica baseada no ID.
	 * * @param int $user_id ID do usuário.
	 *
	 * @param string $notification_id ID único da notificação.
	 * @return bool
	 */
	public function dismiss_notification( int $user_id, string $notification_id ): bool {
		$notifications = $this->get_notifications( $user_id );

		$new_notifications = array_filter(
			$notifications,
			function ( $n ) use ( $notification_id ) {
				return isset( $n['id'] ) && $n['id'] !== $notification_id;
			}
		);

		return update_user_meta( $user_id, self::META_KEY, array_values( $new_notifications ) );
	}

	/**
	 * Limpa todas as notificações do usuário.
	 * * @param int $user_id ID do usuário.
	 *
	 * @return bool
	 */
	public function clear_all_notifications( int $user_id ): bool {
		return update_user_meta( $user_id, self::META_KEY, array() );
	}
}
