<?php
/**
 * View: Tela de Estrutura de Conteúdo (Árvore Hierárquica).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface\Dashboard\Views
 * @since      0.1.0
 *
 * @var string $sults_tree_html         HTML pré-renderizado da árvore de posts (lista aninhada/sortable).
 * @var array  $sults_categories        Lista de categorias (WP_Term) para os dropdowns.
 * @var array  $sults_authors           Lista de usuários (WP_User) gerais/redatores.
 * @var array  $sults_proofreaders      Lista de usuários (WP_User) com role de Corretor.
 * @var array  $sults_designers         Lista de usuários (WP_User) com role de Designer.
 * @var array  $sults_potential_parents Lista de posts que podem servir de "Pai" para novos conteúdos.
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

				<div id="drawer-workflow-section" class="sults-workflow-box" style="display:none;">
					<h4 class="sults-section-title">Próxima Etapa</h4>
					<p class="sults-workflow-desc">Mova este conteúdo para a próxima fase do fluxo editorial.</p>
					<div id="drawer-workflow-actions" class="sults-actions-grid">
						</div>
				</div>

		

				<div class="sults-info-grid three-columns">
					<div class="sults-info-group">
						<label>REDATOR</label>
						<div class="sults-author-block">
							<img id="drawer-author-avatar" src="" alt="Avatar" class="sults-avatar-img">
							<span id="drawer-author-name"></span>
						</div>
					</div>
					
					<div class="sults-info-group">
						<label>CORRETOR</label>
						<div class="sults-author-block">
							<span id="drawer-proofreader-name" class="sults-meta-value">-</span>
						</div>
					</div>

					<div class="sults-info-group">
						<label>DESIGNER</label>
						<div class="sults-author-block">
							<span id="drawer-designer-name" class="sults-meta-value">-</span>
						</div>
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
							
							<div class="sults-form-group">
								<label for="quick-edit-author">Redator (Autor)</label>
								<select name="post_author" id="quick-edit-author" class="sults-input">
									<?php foreach ( $sults_authors as $sults_author ) : ?>
										<option value="<?php echo esc_attr( $sults_author->ID ); ?>"><?php echo esc_html( $sults_author->display_name ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div class="sults-info-grid">
							<div class="sults-form-group">
								<label for="quick-edit-proofreader">Corretor Responsável</label>
								<select name="proofreader_id" id="quick-edit-proofreader" class="sults-input">
									<option value="0">-- Nenhum --</option>
									<?php foreach ( $sults_proofreaders as $sults_proofreader ) : ?>
										<option value="<?php echo esc_attr( $sults_proofreader->ID ); ?>">
											<?php echo esc_html( $sults_proofreader->display_name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="sults-form-group">
								<label for="quick-edit-designer">Designer Responsável</label>
								<select name="designer_id" id="quick-edit-designer" class="sults-input">
									<option value="0">-- Nenhum --</option>
									<?php foreach ( $sults_designers as $sults_designer ) : ?>
										<option value="<?php echo esc_attr( $sults_designer->ID ); ?>">
											<?php echo esc_html( $sults_designer->display_name ); ?>
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
							<button type="submit" class="button button-primary" id="btn-save-quick-edit">Salvar Dados</button>
						</div>
					</form>
				</div>

				<div class="sults-drawer-footer">
					<a id="drawer-btn-view" href="#" target="_blank" class="button sults-btn-view"><span class="dashicons dashicons-visibility"></span> Ver Página</a>
					<a id="drawer-btn-edit" href="#" class="button button-primary sults-btn-edit"><span class="dashicons dashicons-edit"></span> Editar Conteúdo</a>
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
							<option value="0" data-slug="" data-parent-slug="">Sem Categoria</option>
							<?php
							foreach ( $sults_categories as $sults_cat ) :
								$sults_level  = isset( $sults_cat->depth_level ) ? (int) $sults_cat->depth_level : 0;
								$sults_indent = str_repeat( '— ', $sults_level );
								$sults_style  = ( $sults_level === 0 );

								$sults_parent_slug = '';
								$sults_ancestors   = get_ancestors( $sults_cat->term_id, 'category' );
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
					
					<div class="sults-info-grid">
						<div class="sults-form-group">
							<label for="new-post-author">Redator</label>
							<select id="new-post-author" name="author_id" class="sults-input">
								<option value="<?php echo get_current_user_id(); ?>" selected>Eu (<?php echo wp_get_current_user()->display_name; ?>)</option>
								<?php foreach ( $sults_authors as $sults_author ) : ?>
									<?php if ( $sults_author->ID !== get_current_user_id() ) : ?>
										<option value="<?php echo esc_attr( $sults_author->ID ); ?>">
											<?php echo esc_html( $sults_author->display_name ); ?>
										</option>
									<?php endif; ?>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="sults-form-group">
							<label for="new-post-proofreader">Corretor</label>
							<select id="new-post-proofreader" name="proofreader_id" class="sults-input">
								<option value="0">-- Definir depois --</option>
								<?php foreach ( $sults_proofreaders as $sults_proofreader ) : ?>
									<option value="<?php echo esc_attr( $sults_proofreader->ID ); ?>">
										<?php echo esc_html( $sults_proofreader->display_name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="sults-form-group">
							<label for="new-post-designer">Designer</label>
							<select id="new-post-designer" name="designer_id" class="sults-input">
								<option value="0">-- Definir depois --</option>
								<?php foreach ( $sults_designers as $sults_designer ) : ?>
									<option value="<?php echo esc_attr( $sults_designer->ID ); ?>">
										<?php echo esc_html( $sults_designer->display_name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
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

					<div style="background: #f6f7f7; padding: 15px; border-radius: 4px; border: 1px solid #dcdcde; margin-bottom: 20px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 13px; text-transform: uppercase; color: #646970;">Briefing para o Redator</h4>
                        
                        <div class="sults-form-group">
                            <label for="briefing-objective">Objetivo do Conteúdo</label>
                            <textarea id="briefing-objective" name="briefing_objective" class="sults-input" rows="3" placeholder="Ex: Explicar ao cliente como instalar o software passo a passo..."></textarea>
                        </div>

                        <div class="sults-info-grid">
                            <div class="sults-form-group">
                                <label for="briefing-type">Tipo de Texto</label>
                                <select id="briefing-type" name="briefing_type" class="sults-input">
                                    <option value="artigo">Artigo de Blog</option>
                                    <option value="pagina_vendas">Página de Vendas</option>
                                    <option value="visao_geral">Visão Geral / Institucional</option>
                                    <option value="segmento">Página de Segmento</option>
                                    <option value="ajuda">Central de Ajuda (Tutorial)</option>
                                </select>
                            </div>
                            
                            <div class="sults-form-group">
                                <label for="briefing-style">Forma de Escrita</label>
                                <input type="text" id="briefing-style" name="briefing_style" class="sults-input" placeholder="Ex: Formal, Técnico, Descontraído...">
                            </div>
                        </div>
                         
                        <div class="sults-form-group">
                             <label for="briefing-deadline">Prazo de Entrega</label>
                             <input type="date" id="briefing-deadline" name="briefing_deadline" class="sults-input">
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