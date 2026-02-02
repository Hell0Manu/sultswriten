<?php
/**
 * Interface de Envio de E-mails.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */

namespace Sults\Writen\Contracts;

/**
 * Interface MailerInterface.
 *
 * Abstrai o envio de e-mails transacionais do sistema.
 */
interface MailerInterface {
	/**
	 * Envia um e-mail para um usuário.
	 *
	 * @since 0.1.0
	 * @param int    $user_id ID do destinatário.
	 * @param string $subject Assunto do e-mail.
	 * @param string $message Corpo da mensagem (HTML).
	 * @param array  $options Opções extras (botão de ação, cor, template).
	 * @return bool Sucesso no envio.
	 */
	public function send( int $user_id, string $subject, string $message, array $options = array() ): bool;
}