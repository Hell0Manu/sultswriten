<?php
/**
 * Gerenciador de Notificações do Workflow.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Notifications
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Notifications;

use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;
use Sults\Writen\Contracts\NotificationRepositoryInterface;
use Sults\Writen\Contracts\MailerInterface;
use Sults\Writen\Workflow\PostStatus\StatusConfig;
use Sults\Writen\Workflow\Permissions\RoleDefinitions;

/**
 * Classe NotificationManager.
 *
 * Responsável por observar eventos do WordPress (mudança de status, troca de autor)
 * e disparar alertas para os usuários envolvidos.
 *
 * Tipos de Notificação:
 * 1. Interna (Dashboard): Registra mensagens no repositório para serem exibidas no "sininho".
 * 2. Externa (E-mail): Envia e-mails transacionais (via `MailerInterface`) para ações críticas
 * como solicitação de ajustes ou novas tarefas de design.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Notifications
 * @author     Sults
 * @since      0.1.0
 */
class NotificationManager {

	/** @var WPUserProviderInterface Serviço de usuários. */
	private WPUserProviderInterface $user_provider;

	/** @var WPPostStatusProviderInterface Serviço de status. */
	private WPPostStatusProviderInterface $status_provider;

	/** @var NotificationRepositoryInterface Persistência de notificações internas. */
	private NotificationRepositoryInterface $notification_repository;

	/** @var MailerInterface Serviço de envio de e-mail. */
	private MailerInterface $mailer;

	/**
	 * Construtor.
	 */
	public function __construct(
		WPUserProviderInterface $user_provider,
		WPPostStatusProviderInterface $status_provider,
		NotificationRepositoryInterface $notification_repository,
		MailerInterface $mailer
	) {
		$this->user_provider           = $user_provider;
		$this->status_provider         = $status_provider;
		$this->notification_repository = $notification_repository;
		$this->mailer                  = $mailer;
	}

	/**
	 * Registra os hooks de observação.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		// Dispara quando o status do post muda (ex: draft -> pending).
		add_action( 'transition_post_status', array( $this, 'notify_author_on_status_change' ), 10, 3 );
		
		// Dispara quando o post é salvo (usado para detectar troca de autor).
		add_action( 'post_updated', array( $this, 'notify_on_author_assignment' ), 10, 3 );
	}

	/**
	 * Lógica principal de notificação baseada em fluxo.
	 *
	 * Executa:
	 * 1. Notificação genérica interna para o autor quando o status muda (se não foi ele quem mudou).
	 * 2. E-mail de "Solicitação de Ajuste" (Texto ou Imagem) para o autor.
	 * 3. E-mail de "Nova Tarefa" para o time de Design quando entra em "Pendente de Imagem".
	 *
	 * @since 0.1.0
	 *
	 * @param string   $new_status O novo status do post.
	 * @param string   $old_status O status anterior.
	 * @param \WP_Post $sults_post O objeto do post.
	 * @return void
	 */
	public function notify_author_on_status_change( string $new_status, string $old_status, \WP_Post $sults_post ): void {
		// Ignora rascunhos automáticos, mudanças para o mesmo status ou post types irrelevantes.
		if ( $new_status === $old_status || $new_status === 'auto-draft' || $sults_post->post_type !== 'post' ) {
			return;
		}

		$current_user_id = $this->user_provider->get_current_user_id();

		// 1. Notificação Interna (Dashboard).
		// Só notifica se a mudança foi feita por outra pessoa (Editor/Corretor).
		if ( $current_user_id !== (int) $sults_post->post_author ) {
			$sults_status_obj = $this->status_provider->get_status_object( $new_status );
			$status_label     = ( $sults_status_obj && isset( $sults_status_obj->label ) ) ? $sults_status_obj->label : $new_status;

			$msg = sprintf(
				'O status do seu artigo <strong>"%s"</strong> mudou para <span class="sults-status-badge sults-status-%s">%s</span>.',
				$sults_post->post_title,
				esc_attr( $new_status ),
				esc_html( $status_label )
			);

			$notification = array(
				'id'      => uniqid(),
				'time'    => time(),
				'msg'     => $msg,
				'post_id' => $sults_post->ID,
				'read'    => false,
			);

			$this->notification_repository->add_notification( $sults_post->post_author, $notification );
		}

		// 2. Notificação de E-mail: Solicitação de Ajustes (Loop de Feedback).
		if ( $new_status === StatusConfig::TEXT_ADJUSTMENT || $new_status === StatusConfig::IMAGE_ADJUSTMENT ) {
			$color     = '#ff8914'; // Laranja (Alerta).
			$edit_link = get_edit_post_link( $sults_post->ID, 'raw' );

			$tipo_ajuste = ( $new_status === StatusConfig::IMAGE_ADJUSTMENT ) ? 'a imagem' : 'o texto';

			$this->mailer->send(
				$sults_post->post_author,
				'Seu artigo precisa de ajustes',
				sprintf( 
					'<p>Olá! O artigo <strong>"%s"</strong> foi revisado e retornou para ajustar %s. Por favor, verifique os comentários na plataforma.</p>', 
					esc_html( $sults_post->post_title ),
					$tipo_ajuste
				),
				array(
					'color'      => $color,
					'link'       => $edit_link,
					'link_label' => 'Acessar para Ajustar',
				)
			);
		}

		// 3. Notificação de E-mail: Equipe de Design (Workflow Paralelo).
		if ( $new_status === StatusConfig::PENDING_IMAGE ) {

			// Busca todos os usuários com papel de Designer.
			$designers = get_users( array( 'role' => RoleDefinitions::DESIGNER ) );

			if ( ! empty( $designers ) ) {
				$color     = '#7f5aed'; // Roxo (Design).
				$edit_link = get_edit_post_link( $sults_post->ID, 'raw' );

				foreach ( $designers as $designer ) {
					$this->mailer->send(
						$designer->ID,
						'Nova Solicitação de Design',
						sprintf(
							'<p>Olá %s! O artigo <strong>"%s"</strong> está aguardando criação de imagens/mídia.</p>',
							esc_html( $designer->display_name ),
							esc_html( $sults_post->post_title )
						),
						array(
							'color'      => $color,
							'link'       => $edit_link,
							'link_label' => 'Acessar para Criar Arte',
						)
					);
				}
			}
		}
	}

	/**
	 * Notifica quando um post é atribuído a um novo autor.
	 *
	 * @since 0.1.0
	 *
	 * @param int      $sults_post_id      ID do post.
	 * @param \WP_Post $sults_post_after   Objeto do post após atualização.
	 * @param \WP_Post $sults_post_before  Objeto do post antes da atualização.
	 * @return void
	 */
	public function notify_on_author_assignment( int $sults_post_id, \WP_Post $sults_post_after, \WP_Post $sults_post_before ): void {
		if ( $sults_post_after->post_type !== 'post' ) {
			return;
		}

		// Verifica se o ID do autor mudou.
		if ( (int) $sults_post_after->post_author !== (int) $sults_post_before->post_author ) {
			$new_author_id = (int) $sults_post_after->post_author;

			$this->mailer->send(
				$new_author_id,
				'Novo artigo atribuído a você',
				sprintf( '<p>O artigo <strong>"%s"</strong> foi atribuído a você no painel da SULTS.</p>', esc_html( $sults_post_after->post_title ) ),
				array(
					'color'      => '#00acac', // Verde (Sucesso/Novo).
					'link'       => get_edit_post_link( $sults_post_after->ID, 'raw' ),
					'link_label' => 'Ver Artigo',
				)
			);
		}
	}
}