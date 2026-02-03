<?php
/**
 * View: Tela Principal de Exportação.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Opções para o filtro de topo E para o dropdown da linha
$sults_status_options = array(
	'publish'     => 'Publicado',
	'pending_pub' => 'Aguardando Pub.',
	'suspended'   => 'Suspenso',
);
?>
<div class="wrap sults-page-container">
	
	<form method="get" class="sults-filter-bar">
		<input type="hidden" name="page" value="<?php echo esc_attr( \Sults\Writen\Interface\Dashboard\ExportController::PAGE_SLUG ); ?>" />
		
		<div class="sults-filters-left">

			<div class="sults-select-wrapper">
				<?php echo $sults_author_dropdown; // phpcs:ignore ?>
			</div>

			<div class="sults-select-wrapper">
				<?php echo $sults_categories_dropdown; // phpcs:ignore ?>
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
					<th>Título da página</th>
					<th>Link</th>
					<th>Status</th>
					<th>Realização</th>
					<th style="text-align: center;">Autor</th>
					<th style="text-align: center;">Download</th>
				</tr>
			</thead>
			<tbody>
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();
					$sults_post = get_post();

					$sultswriten_author_id  = $sults_post->post_author;
					$sultswriten_categories = get_the_category();

					$sultswriten_cat_name  = 'Sem Categoria';
					$sultswriten_cat_color = \Sults\Writen\Interface\CategoryColorManager::DEFAULT_COLOR;

					if ( ! empty( $sultswriten_categories ) ) {
						$sultswriten_cat_obj    = $sultswriten_categories[0];
						$sultswriten_cat_name   = $sultswriten_cat_obj->name;
						$sultswriten_cat_color  = \Sults\Writen\Interface\CategoryColorManager::get_color( $sultswriten_cat_obj->term_id );
					}
					?>
					<tr id="row-<?php the_ID(); ?>">
						<td>
							<span class="sults-status-badge" style="background-color: <?php echo esc_attr( $sultswriten_cat_color ); ?>; color: #fff;">
								<?php echo esc_html( $sultswriten_cat_name ); ?>
							</span>
						</td>

						<td>
							<div class="sults-title-cell">
								<a href="<?php echo esc_url( get_permalink() ); ?>"> <?php the_title(); ?> </a>
							</div>
						</td>

						<td>
							<?php $sultswriten_display_path = \Sults\Writen\Utils\PathHelper::get_relative_path( get_the_ID() ); ?>
							<a href="<?php echo esc_url( get_permalink() ); ?>" target="_blank" style="color: #3c434a; text-decoration: none; font-size: 12px;">
								<?php echo esc_html( $sultswriten_display_path ); ?>
							</a>
						</td>

						<td>
							<select class="sults-status-changer" 
									data-post-id="<?php the_ID(); ?>" 
									data-nonce="<?php echo wp_create_nonce( 'sults_export_status_nonce' ); ?>"
									style="font-size: 12px; padding: 2px 24px 2px 8px; border-radius: 4px; 
									       border-color: #ddd; background: #fff; cursor: pointer;">
								
								<?php foreach ( $sults_status_options as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $sults_post->post_status, $slug ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<span class="spinner" style="float:none; margin: 0 0 0 5px;"></span>
						</td>

						<td><?php echo get_the_date( 'd/m/Y' ); ?></td>

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
										'action'   => 'sults_export_download', 
										'post_id'  => $sults_post->ID,
										'_wpnonce' => wp_create_nonce( 'sults_export_' . $sults_post->ID ),
									),
									admin_url( 'admin-post.php' ) 
								);
								?>

								<a href="<?php echo esc_url( $sultswriten_preview_url ); ?>" class="sults-icon-btn" title="Visualizar Código">
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
		// phpcs:ignore
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
		// Filtro de topo (recarrega a página)
		$('.sults-filter-select').on('change', function() {
			$(this).closest('form').submit();
		});

		// Mudança de Status via AJAX (na linha da tabela)
		$('.sults-status-changer').on('change', function() {
			var $select = $(this);
			var $row    = $select.closest('tr');
			var $spinner = $row.find('.spinner');
			var newVal  = $select.val();
			var postId  = $select.data('post-id');
			var nonce   = $select.data('nonce');

			$select.prop('disabled', true);
			$spinner.addClass('is-active');

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'sults_export_change_status',
					security: nonce,
					post_id: postId,
					status: newVal
				},
				success: function(res) {
					$spinner.removeClass('is-active');
					$select.prop('disabled', false);

					if(res.success) {
						// Feedback visual simples (piscar verde)
						$select.css('background-color', '#e6ffe6');
						setTimeout(function() {
							$select.css('background-color', '#fff');
						}, 1000);
					} else {
						alert('Erro: ' + res.data);
					}
				},
				error: function() {
					$spinner.removeClass('is-active');
					$select.prop('disabled', false);
					alert('Erro de conexão.');
				}
			});
		});
	});
</script>