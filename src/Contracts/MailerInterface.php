<?php
namespace Sults\Writen\Contracts;

interface MailerInterface {
    /**
     * Envia um e-mail.
     * * @param int    $user_id ID do destinatário.
     * @param string $subject Assunto.
     * @param string $message Mensagem (HTML).
     * @param array  $options Opções extras (link, label_link, color).
     */
    public function send( int $user_id, string $subject, string $message, array $options = [] ): bool;
}