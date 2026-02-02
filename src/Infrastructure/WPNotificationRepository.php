<?php
/**
 * Implementação do repositório de notificações.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */

namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\NotificationRepositoryInterface;

/**
 * Classe WPNotificationRepository.
 *
 * Implementação concreta da persistência de notificações utilizando a tabela
 * `usermeta` do WordPress. As notificações são armazenadas como um array serializado.
 *
 * Esta implementação define um limite rígido (MAX_NOTIFICATIONS) para evitar
 * o crescimento descontrolado do banco de dados.
 *
 * @see NotificationRepositoryInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @author     Sults
 * @since      0.1.0
 */
class WPNotificationRepository implements NotificationRepositoryInterface {

	/**
	 * Chave utilizada no banco de dados (wp_usermeta).
	 *
	 * @var string
	 */
	private const META_KEY = '_sults_user_notifications';

	/**
	 * Número máximo de notificações mantidas no histórico do usuário.
	 *
	 * @var int
	 */
	private const MAX_NOTIFICATIONS = 20;

	/**
	 * Adiciona uma nova notificação à lista de um usuário.
	 *
	 * Adiciona a notificação no início da lista (LIFO) e garante que
	 * a lista não exceda o limite de `MAX_NOTIFICATIONS`, descartando as antigas.
	 *
	 * @since 0.1.0
	 *
	 * @param int   $user_id      ID do usuário.
	 * @param array $notification Dados da notificação (id, mensagem, tipo).
	 * @return bool True se atualizado com sucesso.
	 */
	public function add_notification( int $user_id, array $notification ): bool {
		$user_notifs = $this->get_notifications( $user_id );

		// Adiciona ao topo.
		array_unshift( $user_notifs, $notification );

		// Corta para manter apenas as N mais recentes.
		$user_notifs = array_slice( $user_notifs, 0, self::MAX_NOTIFICATIONS );

		return update_user_meta( $user_id, self::META_KEY, $user_notifs );
	}

	/**
	 * Obtém todas as notificações de um usuário.
	 *
	 * @since 0.1.0
	 *
	 * @param int $user_id ID do usuário.
	 * @return array Lista de notificações ou array vazio se não houver nada.
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
	 *
	 * Filtra o array de notificações removendo o item que corresponde ao ID fornecido
	 * e reindexa o array antes de salvar.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $user_id         ID do usuário.
	 * @param string $notification_id ID único da notificação a ser removida.
	 * @return bool True se atualizado com sucesso.
	 */
	public function dismiss_notification( int $user_id, string $notification_id ): bool {
		$notifications = $this->get_notifications( $user_id );

		$new_notifications = array_filter(
			$notifications,
			function ( $n ) use ( $notification_id ) {
				// Mantém apenas se o ID não bater ou se não tiver ID (segurança).
				return isset( $n['id'] ) && $n['id'] !== $notification_id;
			}
		);

		// array_values é crucial para reindexar o array antes de serializar no JSON/Banco.
		return update_user_meta( $user_id, self::META_KEY, array_values( $new_notifications ) );
	}

	/**
	 * Limpa todas as notificações do usuário.
	 *
	 * Substitui o valor atual por um array vazio.
	 *
	 * @since 0.1.0
	 *
	 * @param int $user_id ID do usuário.
	 * @return bool True se atualizado com sucesso.
	 */
	public function clear_all_notifications( int $user_id ): bool {
		return update_user_meta( $user_id, self::META_KEY, array() );
	}
}