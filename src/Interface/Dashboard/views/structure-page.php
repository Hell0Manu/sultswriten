<?php
/**
 * View para a página de Estrutura.
 *
 * @var string   $tree_html         HTML da árvore de posts.
 * @var array    $categories        Lista de categorias.
 * @var array    $sidebars          Lista de sidebars.
 * @var array    $authors           Lista de autores.
 * @var array    $all_statuses      Lista de slugs de status.
 * @var array    $potential_parents Lista de posts que podem ser pais.
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap">
	<div class="sults-header-row">
		<h1>Estrutura de Conteúdo</h1>
		<button id="btn-open-new-post" class="button button-primary sults-btn-large">
			<span class="dashicons dashicons-plus-alt2"></span> Nova Página
		</button>
	</div>

	<div class="sults-structure-wrapper">
		<?php echo $tree_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
									<?php foreach ( $all_statuses as $status_slug ) :
										$status_obj = get_post_status_object( $status_slug );
										$label      = $status_obj ? $status_obj->label : ucfirst( $status_slug );
										?>
										<option value="<?php echo esc_attr( $status_slug ); ?>"><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="sults-form-group">
								<label for="quick-edit-author">Autor</label>
								<select name="post_author" id="quick-edit-author" class="sults-input">
									<?php foreach ( $authors as $author ) : ?>
										<option value="<?php echo esc_attr( $author->ID ); ?>"><?php echo esc_html( $author->display_name ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div class="sults-info-grid">
							<div class="sults-form-group">
								<label for="quick-edit-category">Categoria</label>
								<select name="post_category" id="quick-edit-category" class="sults-input">
									<option value="0">Sem Categoria</option>
									<?php foreach ( $categories as $cat ) : ?>
										<option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="sults-form-group">
								<label for="quick-edit-sidebar">Sidebar</label>
								<select name="post_sidebar" id="quick-edit-sidebar" class="sults-input">
									<option value="0">Nenhuma</option>
									<?php if ( ! is_wp_error( $sidebars ) ) : ?>
										<?php foreach ( $sidebars as $sidebar ) : ?>
											<option value="<?php echo esc_attr( $sidebar->term_id ); ?>"><?php echo esc_html( $sidebar->name ); ?></option>
										<?php endforeach; ?>
									<?php endif; ?>
								</select>
							</div>
						</div>

						<div class="sults-info-grid">
							<div class="sults-form-group">
								<label for="quick-edit-parent">Post Pai</label>
								<select name="post_parent" id="quick-edit-parent" class="sults-input">
									<option value="0">Nenhum (Raiz)</option>
									<?php foreach ( $potential_parents as $p ) : ?>
										<option value="<?php echo esc_attr( $p->ID ); ?>"><?php echo esc_html( $p->post_title ); ?></option>
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
				<button type="button" class="sults-modal-close"><span class="dashicons dashicons-no-alt"></span></button>
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
            <option value="0" data-slug="">Sem Categoria</option>
            <?php foreach ( $categories as $cat ) : ?>
                <option value="<?php echo esc_attr( $cat->term_id ); ?>" data-slug="<?php echo esc_attr( $cat->slug ); ?>"><?php echo esc_html( $cat->name ); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

	<div class="sults-form-group" id="group-new-post-sidebar" style="display:none;">
        <label for="new-post-sidebar">Selecione a Sidebar</label>
        <select id="new-post-sidebar" name="sidebar_id" class="sults-input" style="max-width: 100%">
            <option value="0">Nenhuma</option>
            <?php if ( ! empty( $sidebars ) && ! is_wp_error( $sidebars ) ) : ?>
                <?php foreach ( $sidebars as $sidebar ) : ?>
                    <option value="<?php echo esc_attr( $sidebar->term_id ); ?>"><?php echo esc_html( $sidebar->name ); ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>

    <div class="sults-form-group" id="group-new-post-parent" style="display:none;">
        <label for="new-post-parent">Post Pai (Raiz)</label>
        <select id="new-post-parent" name="parent_id" class="sults-input" style="max-width: 100%">
            <option value="0" selected>Nenhum (Raiz)</option>
            <?php foreach ( $potential_parents as $p ) : 
                $cats   = get_the_category( $p->ID );
                $cat_id = ! empty( $cats ) ? $cats[0]->term_id : 0;
            ?>
                <option value="<?php echo esc_attr( $p->ID ); ?>" data-cat-id="<?php echo esc_attr( $cat_id ); ?>"><?php echo esc_html( $p->post_title ); ?></option>
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