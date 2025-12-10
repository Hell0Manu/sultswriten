<?php
/**
 * View: Tela inicial do Workspace (Lista de Tarefas).
 * Variáveis disponíveis: $my_posts (WP_Query), $notifications (array), $unread_count (int).
 */

use Sults\Writen\Workflow\PostStatus\PostStatusRegistrar;
use Sults\Writen\Interface\CategoryColorManager; 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap sults-dashboard-wrap">
	<div>
	<h1>DASHBOARD</h1>
	<p style='margin:0'>Bem-vindo ao Sults Writen. Nessa página você encontra os conteudos atribuidos a você</p>
	</div>
	

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
									<th style="width: 15%; text-align-last: center;">Status</th>
									<th style="width: 15%; text-align: right;">Ação</th>
								</tr>
							</thead>
							<tbody>
<?php
                                while ( $my_posts->have_posts() ) :
                                    $my_posts->the_post();

                                    $sultswriten_categories = get_the_category();
                                    $sultswriten_cat_name   = '—';
                                    
                                    $sultswriten_cat_color  = CategoryColorManager::DEFAULT_COLOR; 

                                    if ( ! empty( $sultswriten_categories ) ) {
                                        $sultswriten_cat_obj = $sultswriten_categories[0];
                                        $sultswriten_cat_name = $sultswriten_cat_obj->name;
                                        
                                        $sultswriten_cat_color = CategoryColorManager::get_color( $sultswriten_cat_obj->term_id );
                                    }

                                    $sultswriten_post_status = get_post_status();
                                    $sultswriten_status_obj  = get_post_status_object( $sultswriten_post_status );

                                    $sultswriten_status_lbl  = $sultswriten_status_obj ? $sultswriten_status_obj->label : $sultswriten_post_status;
                                    $sultswriten_badge_class = 'sults-status-badge sults-status-' . esc_attr( $sultswriten_post_status );

                                    $sultswriten_is_locked = $sultswriten_is_restricted_user && in_array( $sultswriten_post_status, $sultswriten_restricted_statuses, true );
                                    ?>
									<tr>
										<td>
											<span style=" background: <?php echo esc_attr( $sultswriten_cat_color ); ?>; color: white; padding: 0px 8px; border-radius: 12px; font-size: 11px; font-weight: 500;">
												<?php echo esc_html( $sultswriten_cat_name ); ?>
											</span>
										</td>
										<td>
											<strong><a href="<?php echo esc_url( get_edit_post_link() ); ?>" style="color: #1d2327; text-decoration: none;">
												<?php the_title(); ?>
											</a></strong>
										</td>
										<td>
											<?php echo esc_html( get_the_modified_date( 'd/m/Y' ) ); ?>
										</td>
										<td style="text-align-last: center;">
											<span class="<?php echo esc_attr( $sultswriten_badge_class ); ?>">
												<?php echo esc_html( $sultswriten_status_lbl ); ?>
											</span>
										</td>
										<td style="text-align: right;">
											
											<?php
											if ( $sultswriten_is_locked ) :
												?>
												
												<a href="<?php echo esc_url( get_permalink() ); ?>" target="_blank" class="button button-small" title="Visualizar" style="display: inline-flex; align-items: center; justify-content: center; width: 32px; padding: 0;">
													<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
														<path d="M8.00003 3.60002C6.37003 3.60002 5.03003 4.34002 4.00253 5.29252C3.04003 6.18752 2.37503 7.25002 2.03503 8.00002C2.37503 8.75002 3.04003 9.81252 4.00003 10.7075C5.03003 11.66 6.37003 12.4 8.00003 12.4C9.63003 12.4 10.97 11.66 11.9975 10.7075C12.96 9.81252 13.625 8.75002 13.965 8.00002C13.625 7.25002 12.96 6.18752 12 5.29252C10.97 4.34002 9.63003 3.60002 8.00003 3.60002ZM3.18503 4.41502C4.36253 3.32002 5.98003 2.40002 8.00003 2.40002C10.02 2.40002 11.6375 3.32002 12.815 4.41502C13.985 5.50252 14.7675 6.80002 15.14 7.69252C15.2225 7.89002 15.2225 8.11002 15.14 8.30752C14.7675 9.20002 13.985 10.5 12.815 11.585C11.6375 12.68 10.02 13.6 8.00003 13.6C5.98003 13.6 4.36253 12.68 3.18503 11.585C2.01503 10.5 1.23253 9.20002 0.862534 8.30752C0.780034 8.11002 0.780034 7.89002 0.862534 7.69252C1.23253 6.80002 2.01503 5.50002 3.18503 4.41502ZM8.00003 10C9.10503 10 10 9.10502 10 8.00002C10 6.89502 9.10503 6.00002 8.00003 6.00002C7.98253 6.00002 7.96753 6.00002 7.95003 6.00002C7.98253 6.12752 8.00003 6.26252 8.00003 6.40002C8.00003 7.28252 7.28253 8.00002 6.40003 8.00002C6.26253 8.00002 6.12753 7.98252 6.00003 7.95002C6.00003 7.96752 6.00003 7.98252 6.00003 8.00002C6.00003 9.10502 6.89503 10 8.00003 10ZM8.00003 4.80002C8.84873 4.80002 9.66266 5.13717 10.2628 5.73728C10.8629 6.3374 11.2 7.15133 11.2 8.00002C11.2 8.84872 10.8629 9.66265 10.2628 10.2628C9.66266 10.8629 8.84873 11.2 8.00003 11.2C7.15134 11.2 6.33741 10.8629 5.73729 10.2628C5.13718 9.66265 4.80003 8.84872 4.80003 8.00002C4.80003 7.15133 5.13718 6.3374 5.73729 5.73728C6.33741 5.13717 7.15134 4.80002 8.00003 4.80002Z" fill="#50575e"/>
													</svg>
												</a>

											<?php else : ?>

												<a href="<?php echo esc_url( get_edit_post_link() ); ?>" class="button button-small" title="Editar" style="display: inline-flex; align-items: center; justify-content: center; width: 32px; padding: 0;">
													<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
														<path d="M12.625 3.0725L12.9275 3.375C13.1625 3.61 13.1625 3.99 12.9275 4.2225L12.2 4.9525L11.0475 3.8L11.775 3.0725C12.01 2.8375 12.39 2.8375 12.6225 3.0725H12.625ZM6.84498 8.005L10.2 4.6475L11.3525 5.8L7.99498 9.155C7.92248 9.2275 7.83248 9.28 7.73498 9.3075L6.27248 9.725L6.68998 8.2625C6.71748 8.165 6.76998 8.075 6.84248 8.0025L6.84498 8.005ZM10.9275 2.225L5.99498 7.155C5.77748 7.3725 5.61998 7.64 5.53748 7.9325L4.82248 10.4325C4.76248 10.6425 4.81998 10.8675 4.97498 11.0225C5.12998 11.1775 5.35498 11.235 5.56498 11.175L8.06498 10.46C8.35998 10.375 8.62748 10.2175 8.84248 10.0025L13.775 5.07249C14.4775 4.36999 14.4775 3.23 13.775 2.5275L13.4725 2.225C12.77 1.5225 11.63 1.5225 10.9275 2.225ZM3.79998 3.2C2.58498 3.2 1.59998 4.185 1.59998 5.4V12.2C1.59998 13.415 2.58498 14.4 3.79998 14.4H10.6C11.815 14.4 12.8 13.415 12.8 12.2V9.4C12.8 9.0675 12.5325 8.8 12.2 8.8C11.8675 8.8 11.6 9.0675 11.6 9.4V12.2C11.6 12.7525 11.1525 13.2 10.6 13.2H3.79998C3.24748 13.2 2.79998 12.7525 2.79998 12.2V5.4C2.79998 4.8475 3.24748 4.4 3.79998 4.4H6.59998C6.93248 4.4 7.19998 4.1325 7.19998 3.8C7.19998 3.4675 6.93248 3.2 6.59998 3.2H3.79998Z" fill="#202527"/>
													</svg>
												</a>

											<?php endif; ?>
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