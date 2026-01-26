<?php

namespace Sults\Writen\Workflow\Notifications;

use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;
use Sults\Writen\Contracts\NotificationRepositoryInterface;
use Sults\Writen\Contracts\MailerInterface;
use Sults\Writen\Workflow\PostStatus\StatusConfig;
use Sults\Writen\Workflow\Permissions\RoleDefinitions;

class NotificationManager {
	private WPUserProviderInterface $user_provider;
	private WPPostStatusProviderInterface $status_provider;
	private NotificationRepositoryInterface $notification_repository;
	private MailerInterface $mailer;

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

	public function register(): void {
		add_action( 'transition_post_status', array( $this, 'notify_author_on_status_change' ), 10, 3 );
		add_action( 'post_updated', array( $this, 'notify_on_author_assignment' ), 10, 3 );
	}

	public function notify_author_on_status_change( string $new_status, string $old_status, \WP_Post $sults_post ): void {
		if ( $new_status === $old_status || $new_status === 'auto-draft' || $sults_post->post_type !== 'post' ) {
			return;
		}

		$current_user_id = $this->user_provider->get_current_user_id();

		// Notificação interna no painel (sininho) se não fui eu que mudei
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

		if ( $new_status === StatusConfig::TEXT_ADJUSTMENT || $new_status === StatusConfig::IMAGE_ADJUSTMENT ) {
			$color     = '#ff8914';
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

		// Notificação para Designers
		if ( $new_status === StatusConfig::PENDING_IMAGE ) {

			$designers = get_users( array( 'role' => RoleDefinitions::DESIGNER ) );

			if ( ! empty( $designers ) ) {

				$color     = '#7f5aed';
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

	public function notify_on_author_assignment( int $sults_post_id, \WP_Post $sults_post_after, \WP_Post $sults_post_before ): void {
		if ( $sults_post_after->post_type !== 'post' ) {
			return;
		}

		if ( (int) $sults_post_after->post_author !== (int) $sults_post_before->post_author ) {
			$new_author_id = (int) $sults_post_after->post_author;

			$this->mailer->send(
				$new_author_id,
				'Novo artigo atribuído a você',
				sprintf( '<p>O artigo <strong>"%s"</strong> foi atribuído a você no painel da SULTS.</p>', esc_html( $sults_post_after->post_title ) ),
				array(
					'color'      => '#00acac',
					'link'       => get_edit_post_link( $sults_post_after->ID, 'raw' ),
					'link_label' => 'Ver Artigo',
				)
			);
		}
	}
}