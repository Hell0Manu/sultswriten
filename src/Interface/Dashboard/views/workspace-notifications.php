<?php
/**
 * Partial: Painel de Notificações.
 *
 * @var array $notifications
 * @var int $unread_count
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php if ( ! empty( $notifications ) ) : ?>
	<div class="sults-notification-panel" style="background: #fff; border-radius: 8px; border: 1px solid #dcdcde; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
		
		<div style="padding: 15px; border-bottom: 1px solid #f0f0f1; background: #fff; display:flex; justify-content: space-between; align-items: center;">
			<h3 style="margin:0; font-size: 14px; font-weight: 600; display:flex; align-items:center; gap: 6px;">
				<span class="dashicons dashicons-bell" style="color: #dba617;"></span> 
				Notificações
			</h3>
			<?php if ( $unread_count > 0 ) : ?>
				<span class="sults-badge-count" style="background: #d63638; color: white; border-radius: 10px; padding: 2px 8px; font-size: 11px; font-weight: bold;">
					<?php echo esc_html( $unread_count ); ?> novas
				</span>
			<?php endif; ?>
		</div>

		<ul style="margin: 0; list-style: none;">
			<?php
			foreach ( $notifications as $sultswriten_notif ) :
				$sultswriten_dismiss_url = wp_nonce_url(
					add_query_arg(
						array(
							'sults_action' => 'dismiss_notif',
							'notif_id'     => $sultswriten_notif['id'],
						)
					),
					'sults_workspace_action'
				);
				?>
				<li style="padding: 12px 15px; border-bottom: 1px solid #f0f0f1; transition: background 0.2s;">
					<div style="font-size: 13px; color: #3c434a; line-height: 1.4; margin-bottom: 6px;">
						<?php echo wp_kses_post( $sultswriten_notif['msg'] ); ?>
					</div>
					
					<div style="display:flex; justify-content: space-between; align-items: center;">
						<span style="color:#a7aaad; font-size: 11px;">
							<?php
							echo esc_html( human_time_diff( $sultswriten_notif['time'] ) . ' atrás' );
							?>
						</span>
						
						<a href="<?php echo esc_url( $sultswriten_dismiss_url ); ?>" style="text-decoration: none; font-size: 11px; color: #2271b1; display: flex; align-items: center; gap: 2px;">
							<span class="dashicons dashicons-yes" style="font-size: 14px; width: 14px; height: 14px;"></span>
							Marcar lido
						</a>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php else : ?>
	<div class="sults-notification-empty" style="background: #fff; border-radius: 8px; border: 1px dashed #dcdcde; padding: 30px; text-align: center; color: #a7aaad;">
		<span class="dashicons dashicons-yes-alt" style="font-size: 32px; width: 32px; height: 32px; margin-bottom: 10px; opacity: 0.5;"></span>
		<p style="margin:0; font-size: 13px;">Tudo em dia! Nenhuma notificação pendente.</p>
	</div>
<?php endif; ?>
