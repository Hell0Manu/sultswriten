<?php
/**
 * View: Tela inicial do Workspace (Lista de Tarefas).
 * Variáveis disponíveis: $my_posts (WP_Query), $notifications (array), $unread_count (int).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap sults-dashboard-wrap">
	<h1 class="wp-heading-inline">DASHBOARD</h1>
	<p>Bem-vindo ao Sults Writen. Nessa página você encontra os conteudos atribuidos a você</p>
	<hr class="wp-header-end">

	<div class="sults-dashboard-grid" style="display: flex; gap: 20px; margin-top: 20px; flex-wrap: wrap;">
		
		<div class="sults-column-main" style="flex: 3; min-width: 300px;">
			<div class="sults-dashboard-card" style="background: #fff; border-radius: 8px; border: 1px solid #dcdcde; overflow: hidden;">
				
				<div class="sults-card-header" style="padding: 15px 8px; border-bottom: 1px solid #f0f0f1; background: #f6f7f7;">
					<h2 style="margin: 0; font-size: 16px; font-weight: 600;">Meus Artigos Recentes</h2>
				</div>

				<div class="sults-card-body">
					<?php if ( $my_posts->have_posts() ) : ?>
						<table class="wp-list-table widefat fixed striped table-view-list posts" style="border: none; box-shadow: none;">
							<thead>
								<tr>
									<th style="width: 15%;">Categoria</th>
									<th style="width: 40%;">Título</th>
									<th style="width: 15%;">Modificado</th>
									<th style="width: 15%;">Status</th>
									<th style="width: 15%; text-align: right;">Ação</th>
								</tr>
							</thead>
							<tbody>
								<?php
								while ( $my_posts->have_posts() ) :
									$my_posts->the_post();
									$sultswriten_categories = get_the_category();
									$sultswriten_cat_name   = ! empty( $sultswriten_categories ) ? $sultswriten_categories[0]->name : '—';

									$sultswriten_post_status = get_post_status();
									$sultswriten_status_obj  = get_post_status_object( $sultswriten_post_status );
									$sultswriten_status_lbl  = $sultswriten_status_obj ? $sultswriten_status_obj->label : $sultswriten_post_status;
									$sultswriten_badge_class = 'sults-status-badge sults-status-' . esc_attr( $sultswriten_post_status );
									?>
								<tr>
									<td>
										<span style="background: #2271b1; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500;">
											<?php echo esc_html( $sultswriten_cat_name ); ?>
										</span>
									</td>
									<td>
										<strong><a href="<?php echo esc_url( get_edit_post_link() ); ?>" style="color: #1d2327; text-decoration: none;">
											<?php the_title(); ?>
										</a></strong>
									</td>
									<td>
										<?php
										echo esc_html( get_the_modified_date( 'd/m/Y' ) );
										?>
									</td>
									<td>
										<span class="<?php echo esc_attr( $sultswriten_badge_class ); ?>">
											<?php echo esc_html( $sultswriten_status_lbl ); ?>
										</span>
									</td>
									<td style="text-align: right;">
										<a href="<?php echo esc_url( get_edit_post_link() ); ?>" class="button button-small">
											Editar
										</a>
									</td>
								</tr>
								<?php endwhile; ?>
							</tbody>
						</table>
					<?php else : ?>
						<div style="padding: 40px; text-align: center; color: #646970;">
							<span class="dashicons dashicons-text-page" style="font-size: 40px; margin-bottom: 10px;"></span>
							<p>Nenhum artigo atribuído a você.</p>
							<a href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>" class="button button-primary">Criar Novo Artigo</a>
						</div>
					<?php endif; ?>
					<?php wp_reset_postdata(); ?>
				</div>
			</div>
		</div>

		<div class="sults-column-side" style="flex: 1; min-width: 280px;">
			<?php require __DIR__ . '/workspace-notifications.php'; ?>
		</div>

	</div>
</div>