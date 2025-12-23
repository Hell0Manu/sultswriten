<?php
/**
 * View da Home de Exportação.
 *
 * @var \WP_Query $query
 * @var array $filters
 * @var string $categories_dropdown
 * @var string $author_dropdown
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap sults-page-container">
	
	<form method="get" class="sults-filter-bar">
		<input type="hidden" name="page" value="<?php echo esc_attr( \Sults\Writen\Interface\Dashboard\ExportController::PAGE_SLUG ); ?>" />
		
		<div class="sults-filters-left">
			<div class="sults-select-wrapper">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML seguro gerado pelo WP.
				echo $author_dropdown;
				?>
			</div>

			<div class="sults-select-wrapper">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML seguro gerado pelo WP.
				echo $categories_dropdown;
				?>
			</div>
		</div>
		<div class="sults-actions">
			<div class="sults-search-box">
				<span class="dashicons dashicons-search sults-search-icon"></span>
				<input type="search" name="s" class="sults-search-input" placeholder="Search..." value="<?php echo esc_attr( $filters['s'] ); ?>">
			</div>
		</div>
	</form>

	<?php if ( $query->have_posts() ) : ?>
		<table class="sults-modern-table">
			<thead>
				<tr>
					<th>Categoria</th>
					<th>Sidebar</th>
					<th>Título da página</th>
					<th>Link para a página no site</th>
					<th>Realização</th>
					<th style="text-align: center;">Autor</th>
					<th style="text-align: center;">Download</th>
				</tr>
			</thead>
			<tbody>
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();
					global $post;
					$sultswriten_author_id = $post->post_author;

					$sultswriten_categories = get_the_category();
					$sultswriten_cat_name   = 'Sem Categoria';
					$sultswriten_cat_color  = \Sults\Writen\Interface\CategoryColorManager::DEFAULT_COLOR;

					if ( ! empty( $sultswriten_categories ) ) {
						$sultswriten_cat_obj  = $sultswriten_categories[0];
						$sultswriten_cat_name = $sultswriten_cat_obj->name;

						$sultswriten_cat_color = \Sults\Writen\Interface\CategoryColorManager::get_color( $sultswriten_cat_obj->term_id );
					}

					$terms = get_the_terms( get_the_ID(), 'sidebar' );
       			 	$sidebar = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? $terms[0]->name : '—';
					?>
					<tr>
						<td>
							<span class="sults-status-badge" style="background-color: <?php echo esc_attr( $sultswriten_cat_color ); ?>; color: #fff;">
								<?php echo esc_html( $sultswriten_cat_name ); ?>
							</span>
						</td>

						<td>
                <span style="font-weight: 500; color: var(--color-neutral-700);">
                    <?php echo esc_html( $sidebar ); ?>
                </span>
            </td>

						<td>
							<div class="sults-title-cell">
								<a href="<?php echo esc_url( get_permalink() ); ?>"> <?php the_title(); ?> </a>
							</div>
						</td>

						<td>
							<?php
							$sultswriten_path_slug = $post->post_name;
							$sultswriten_path_cat  = '';

							if ( ! empty( $sultswriten_categories ) ) {
								$sultswriten_path_cat = $sultswriten_categories[0]->slug;
							} else {
								$sultswriten_path_cat = 'sem-categoria';
							}

							$sultswriten_display_path = sprintf( '/%s/%s/', $sultswriten_path_cat, $sultswriten_path_slug );
							?>

							<a href="<?php echo esc_url( get_permalink() ); ?>" target="_blank" style="color: #3c434a; text-decoration: none; font-size: 12px;">
								<?php echo esc_html( $sultswriten_display_path ); ?>
							</a>
						</td>

						<td>
							<?php echo get_the_date( 'd/m/Y' ); ?>
						</td>

						<td style="text-align: center;">
							<?php echo get_avatar( $sultswriten_author_id, 32, '', '', array( 'class' => 'sults-avatar' ) ); ?>
						</td>

						<td>
						<div class="sults-download-actions" style="justify-content: center;">
							
							<?php
							$sultswriten_preview_url = add_query_arg(
								array(
									'page'    => \Sults\Writen\Interface\Dashboard\ExportController::PAGE_SLUG,
									'action'  => 'preview',
									'post_id' => get_the_ID(),
								),
								admin_url( 'admin.php' )
							);
							$sultswriten_preview_url = wp_nonce_url( $sultswriten_preview_url, 'sults_preview_' . get_the_ID() );

							$sultswriten_download_url = add_query_arg(
								array(
									'page'     => \Sults\Writen\Interface\Dashboard\ExportController::PAGE_SLUG,
									'action'   => 'download',
									'post_id'  => $post->ID,
									'_wpnonce' => wp_create_nonce( 'sults_export_' . $post->ID ),
								),
								admin_url( 'admin.php' )
							);
							?>

							<a href="<?php echo esc_url( $sultswriten_preview_url ); ?>" class="sults-icon-btn" title="Visualizar Código (Antes/Depois)">
								<span class="dashicons dashicons-visibility"></span> 
							</a>

							<a href="<?php echo esc_url( $sultswriten_download_url ); ?>" class="sults-icon-btn" title="Baixar ZIP">
								<span class="dashicons dashicons-download"></span>
							</a>
						</div>
					</td>
					</tr>
				<?php endwhile; ?>
			</tbody>
		</table>

		<?php
		$sultswriten_big = 999999999;
		echo '<div style="margin-top: 20px; display: flex; justify-content: flex-end;">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML seguro gerado pelo WP.
		echo paginate_links(
			array(
				'base'      => str_replace( $sultswriten_big, '%#%', esc_url( get_pagenum_link( $sultswriten_big ) ) ),
				'format'    => '?paged=%#%',
				'current'   => max( 1, $filters['paged'] ),
				'total'     => $query->max_num_pages,
				'prev_text' => '&lsaquo;',
				'next_text' => '&rsaquo;',
			)
		);
		echo '</div>';
		wp_reset_postdata();
		?>

	<?php else : ?>
		<div style="text-align: center; padding: 50px; background: white; border-radius: 8px;">
			<h3>Nenhum artigo encontrado.</h3>
		</div>
	<?php endif; ?>
</div>

<script>
	jQuery(document).ready(function($) {
		$('.sults-filter-select').on('change', function() {
			$(this).closest('form').submit();
		});
	});
</script>