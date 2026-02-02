<?php
/**
 * Interface de Repositório de Notificações.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

/**
 * Interface NotificationRepositoryInterface.
 *
 * Gerencia a persistência das notificações do sistema (mensagens no painel),
 * permitindo trocar a estratégia de armazenamento (User Meta, Tabela Customizada, etc.).
 */
interface NotificationRepositoryInterface {
	/**
	 * Adiciona uma nova notificação para um usuário.
	 *
	 * @since 0.1.0
	 * @param int   $user_id      ID do destinatário.
	 * @param array $notification Dados da notificação (mensagem, tipo, link).
	 * @return bool
	 */
	public function add_notification( int $user_id, array $notification ): bool;

	/**
	 * Recupera todas as notificações ativas de um usuário.
	 *
	 * @since 0.1.0
	 * @param int $user_id ID do usuário.
	 * @return array Lista de notificações.
	 */
	public function get_notifications( int $user_id ): array;

	/**
	 * Marca uma notificação como lida/removida.
	 *
	 * @since 0.1.0
	 * @param int    $user_id         ID do usuário.
	 * @param string $notification_id ID único da notificação.
	 * @return bool
	 */
	public function dismiss_notification( int $user_id, string $notification_id ): bool;

	/**
	 * Limpa todas as notificações de um usuário.
	 *
	 * @since 0.1.0
	 * @param int $user_id ID do usuário.
	 * @return bool
	 */
	public function clear_all_notifications( int $user_id ): bool;
}