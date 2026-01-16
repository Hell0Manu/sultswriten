<?php
/**
 * View para a página de Estrutura.
 *
 * @var string   $sults_tree_html         HTML da árvore de posts.
 * @var array    $sults_categories        Lista de categorias.
 * @var array    $sults_authors           Lista de autores.
 * @var array    $sults_all_statuses      Lista de slugs de status.
 * @var array    $sults_potential_parents Lista de posts que podem ser pais.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
?>

<div class="wrap">
	<div class="sults-header-row">
		<h1>Estrutura de Conteúdo</h1>
		<button id="btn-open-new-post" class="button button-primary sults-btn-large">
			<span class="dashicons dashicons-plus-alt2"></span> Nova Página
		</button>
	</div>

	<div class="sults-structure-wrapper">
		<?php echo $sults_tree_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>

	<div id="sults-drawer-backdrop" class="sults-drawer-backdrop"></div>
	<div id="sults-detail-drawer" class="sults-drawer">
		<button type="button" class="sults-drawer-close" title="Fechar"><span class="dashicons dashicons-no-alt"></span></button>
		<div class="sults-drawer-body">
			<div class="sults-drawer-loading"><span class="spinner is-active"></span> Carregando...</div>
			<div class="sults-drawer-content" style="display:none;">
				<div class="sults-drawer-header-content">
					<h2 id="drawer-title" class="sults-drawer-title"></h2>
					<div class="sults-drawer-meta-row">
						<span id="drawer-id" class="sults-meta-id"></span>
						<div id="drawer-status"></div>
					</div>
				</div>
				<hr class="sults-drawer-divider">
				<div class="sults-info-group">
					<label>CRIADO POR</label>
					<div class="sults-author-block">
						<img id="drawer-author-avatar" src="" alt="Avatar" class="sults-avatar-img">
						<span id="drawer-author-name"></span>
					</div>
				</div>
				<div class="sults-info-grid">
					<div class="sults-info-group">
						<label>DATA</label>
						<span id="drawer-date" class="sults-info-value"></span>
					</div>
					<div class="sults-info-group">
						<label>CATEGORIA</label>
						<div id="drawer-category" class="sults-category-tag"></div>
					</div>
				</div>
				<div class="sults-seo-box">
					<div class="sults-seo-header"><span class="dashicons dashicons-google"></span> Pré-visualização SEO</div>
					<div class="sults-seo-preview">
						<div id="drawer-seo-title" class="sults-seo-title"></div>
						<div id="drawer-seo-desc" class="sults-seo-desc"></div>
					</div>
				</div>
				<div class="sults-info-group">
					<label>CAMINHO (PATH)</label>
					<div class="sults-path-box">
						<span class="dashicons dashicons-admin-links"></span>
						<span id="drawer-path"></span>
					</div>
				</div>
				
				<div class="sults-quick-edit-section">
					<hr class="sults-drawer-divider">
					<h3 class="sults-quick-edit-title">Edição Rápida</h3>
					<form id="sults-quick-edit-form">
						<input type="hidden" name="post_id" id="quick-edit-id">

						<div class="sults-form-group">
							<label for="quick-edit-title">Título</label>
							<input type="text" name="post_title" id="quick-edit-title" class="sults-input">
						</div>

						<div class="sults-form-group">
							<label for="quick-edit-slug">Slug</label>
							<input type="text" name="post_name" id="quick-edit-slug" class="sults-input">
						</div>

						<div class="sults-info-grid">
							<div class="sults-form-group">
								<label for="quick-edit-status">Status</label>
								<select name="post_status" id="quick-edit-status" class="sults-input">
									<?php
									foreach ( $sults_all_statuses as $sults_status_slug ) :
										$sults_status_obj = get_post_status_object( $sults_status_slug );
										$sults_label      = $sults_status_obj ? $sults_status_obj->label : ucfirst( $sults_status_slug );
										?>
										<option value="<?php echo esc_attr( $sults_status_slug ); ?>"><?php echo esc_html( $sults_label ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="sults-form-group">
								<label for="quick-edit-author">Autor</label>
								<select name="post_author" id="quick-edit-author" class="sults-input">
									<?php foreach ( $sults_authors as $sults_author ) : ?>
										<option value="<?php echo esc_attr( $sults_author->ID ); ?>"><?php echo esc_html( $sults_author->display_name ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div class="sults-info-grid">
							<div class="sults-form-group">
							<label for="quick-edit-category">Categoria</label>
							<select name="post_category" id="quick-edit-category" class="sults-input">
								<option value="0">Sem Categoria</option>
								<?php
								foreach ( $sults_categories as $sults_cat ) :
									$sults_level  = isset( $sults_cat->depth_level ) ? (int) $sults_cat->depth_level : 0;
									$sults_indent = str_repeat( '— ', $sults_level );
									$sults_style  = ( $sults_level === 0 );
									?>
									<option value="<?php echo esc_attr( $sults_cat->term_id ); ?>" style="<?php echo esc_attr( $sults_style ); ?>">
										<?php echo esc_html( $sults_indent . $sults_cat->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						</div>

						<div class="sults-info-grid">
							<div class="sults-form-group">
								<label for="quick-edit-parent">Post Pai</label>
								<select name="post_parent" id="quick-edit-parent" class="sults-input">
									<option value="0">Nenhum (Raiz)</option>
									<?php
									foreach ( $sults_potential_parents as $sults_p ) :
										$sults_level  = isset( $sults_p->depth_level ) ? (int) $sults_p->depth_level : 0;
										$sults_indent = str_repeat( '— ', $sults_level );
										?>
										<option value="<?php echo esc_attr( $sults_p->ID ); ?>">
											<?php echo esc_html( $sults_indent . $sults_p->post_title ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="sults-form-group">
								<label for="quick-edit-password">Senha</label>
								<input type="text" name="post_password" id="quick-edit-password" class="sults-input" placeholder="Opcional">
							</div>
						</div>

						<div class="sults-form-group">
							<label for="quick-edit-date">Data de Publicação</label>
							<input type="datetime-local" name="post_date" id="quick-edit-date" class="sults-input">
						</div>

						<div class="sults-quick-edit-actions">
							<button type="submit" class="button button-primary" id="btn-save-quick-edit">Salvar Alterações</button>
						</div>
					</form>
				</div>

				<div class="sults-drawer-footer">
					<a id="drawer-btn-view" href="#" target="_blank" class="button sults-btn-view"><span class="dashicons dashicons-visibility"></span> Ver Página</a>
					<a id="drawer-btn-edit" href="#" class="button button-primary sults-btn-edit"><span class="dashicons dashicons-edit"></span> Editar Página</a>
				</div>
			</div>
		</div>
	</div>

	<div id="sults-modal-backdrop" class="sults-modal-backdrop">
		<div class="sults-modal">
			<div class="sults-modal-header">
				<h2>Nova Página</h2>
				<button type="button" class="sults-modal-close" st><span class="dashicons dashicons-no-alt"></span></button>
			</div>
			<div class="sults-modal-body">
			<form id="sults-create-post-form">
	
	<div class="sults-form-group">
		<label for="new-post-title">Nome do Post</label>
		<input type="text" id="new-post-title" name="title" class="sults-input" placeholder="Ex: Guia de Instalação" required>
	</div>

	<div class="sults-form-group">
			<label for="new-post-category">Categoria</label>
<select id="new-post-category" name="cat_id" class="sults-input" style="max-width: 100%">
	<option value="" selected disabled>Selecione uma categoria...</option>
	<option value="0" data-slug="" data-parent-slug="">Sem Categoria</option>
	
	<?php
	foreach ( $sults_categories as $sults_cat ) :
		$sults_level  = isset( $sults_cat->depth_level ) ? (int) $sults_cat->depth_level : 0;
		$sults_indent = str_repeat( '— ', $sults_level );
		$sults_style  = ( $sults_level === 0 );

		$sults_parent_slug = '';

		$sults_ancestors = get_ancestors( $sults_cat->term_id, 'category' );

		if ( ! empty( $sults_ancestors ) ) {
			$sults_ancestors = array_reverse( $sults_ancestors );
			$sults_slugs     = array();

			foreach ( $sults_ancestors as $sults_ancestor_id ) {
				$sults_term = get_term( $sults_ancestor_id, 'category' );
				if ( $sults_term && ! is_wp_error( $sults_term ) ) {
					$sults_slugs[] = $sults_term->slug;
				}
			}
			$sults_parent_slug = implode( '/', $sults_slugs );
		}
		?>
		<option 
			value="<?php echo esc_attr( $sults_cat->term_id ); ?>" 
			data-slug="<?php echo esc_attr( $sults_cat->slug ); ?>"
			data-parent-slug="<?php echo esc_attr( $sults_parent_slug ); ?>"
			style="<?php echo esc_attr( $sults_style ); ?>"
		>
			<?php echo esc_html( $sults_indent . $sults_cat->name ); ?>
		</option>
	<?php endforeach; ?>
</select>
	</div>

<div class="sults-form-group" id="group-new-post-parent" style="display:none;">
		<label for="new-post-parent">Post Pai (Raiz)</label>
		<select id="new-post-parent" name="parent_id" class="sults-input" style="max-width: 100%">
			<option value="0" selected>Nenhum (Raiz)</option>
			<?php
			foreach ( $sults_potential_parents as $sults_p ) :
				$sults_cats   = get_the_category( $sults_p->ID );
				$sults_cat_id = ! empty( $sults_cats ) ? $sults_cats[0]->term_id : 0;
				$sults_level  = isset( $sults_p->depth_level ) ? (int) $sults_p->depth_level : 0;
				$sults_indent = str_repeat( '— ', $sults_level );
				?>
				<option value="<?php echo esc_attr( $sults_p->ID ); ?>" data-cat-id="<?php echo esc_attr( $sults_cat_id ); ?>">
					<?php echo esc_html( $sults_indent . $sults_p->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>

	<div class="sults-form-group">
		<label for="new-post-slug">URL do Post (Slug)</label>
		<div class="sults-input-group">
			<span class="sults-input-prefix" id="new-post-slug-prefix">/</span>
			<input type="text" id="new-post-slug" name="slug" class="sults-input" placeholder="guia-de-instalacao">
		</div>
	</div>

	<div class="sults-modal-footer">
		<button type="button" class="button sults-modal-cancel">Cancelar</button>
		<button type="submit" class="button button-primary sults-btn-large">Criar Página</button>
	</div>
</form>
			</div>
		</div>
	</div>
</div>
