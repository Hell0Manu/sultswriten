<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Variáveis disponíveis: $query, $filters, $categories_dropdown, $author_dropdown
?>

<div class="wrap sults-page-container">
    
    <form method="get" class="sults-filter-bar">
        <input type="hidden" name="page" value="<?php echo esc_attr( \Sults\Writen\Interface\Dashboard\ExportController::PAGE_SLUG ); ?>" />
        
        <div class="sults-filters-left">
            <div class="sults-select-wrapper">
                <?php echo $author_dropdown; ?>
            </div>

            <div class="sults-select-wrapper">
                <?php echo $categories_dropdown; ?>
            </div>
        </div>
        <div class="sults-actions">
            <div class="sults-search-box">
                <span class="dashicons dashicons-search sults-search-icon"></span>
                <input type="search" name="s" class="sults-search-input" placeholder="Search..." value="<?php echo esc_attr( $filters['s'] ); ?>">
            </div>

            <div class="sults-actions-right">
                <button type="button" class="sults-btn-primary">
                    Gerar JSP <span class="dashicons dashicons-media-code" style="margin-top: 3px;"></span>
                </button>
            </div>
        </div>
    </form>

    <?php if ( $query->have_posts() ) : ?>
        <table class="sults-modern-table">
            <thead>
                <tr>
                    <th class="sults-checkbox-col"><input type="checkbox" id="cb-select-all-1"></th>
                    <th>Categoria</th>
                    <th>Título da página</th>
                    <th>Link para a página no site</th>
                    <th>Realização</th>
                    <th style="text-align: center;">Autor</th>
                    <th style="text-align: center;">Download</th>
                </tr>
            </thead>
            <tbody>
                <?php while ( $query->have_posts() ) : $query->the_post(); 
                    global $post;
                    $author_id = $post->post_author;



                    $categories = get_the_category();
                    $cat_name   = 'Sem Categoria';
                    $cat_color  = \Sults\Writen\Interface\CategoryColorManager::DEFAULT_COLOR; 

                    if ( ! empty( $categories ) ) {
                        $cat_obj   = $categories[0];
                        $cat_name  = $cat_obj->name;

                        $cat_color = \Sults\Writen\Interface\CategoryColorManager::get_color( $cat_obj->term_id );
                    }
                ?>
                    <tr>
                        <td><input type="checkbox" name="post[]" value="<?php the_ID(); ?>"></td>
                        
                        <td>
                            <span class="sults-status-badge" style="background-color: <?php echo esc_attr( $cat_color ); ?>; color: #fff;">
                                <?php echo esc_html( $cat_name ); ?>
                            </span>
                        </td>

                        <td>
                            <div class="sults-title-cell">
                                <a href="<?php echo get_permalink(); ?>"> <?php the_title(); ?> </a>
                            </div>
                        </td>

                        <td>
                            <a href="<?php echo get_permalink(); ?>" target="_blank" style="color: #3c434a; text-decoration: none; font-size: 12px;">
                                <?php echo esc_url( get_permalink() ); ?>
                            </a>
                        </td>

                        <td>
                            <?php echo get_the_date( 'd/m/Y' ); ?>
                        </td>

                        <td style="text-align: center;">
                            <?php echo get_avatar( $author_id, 32, '', '', array( 'class' => 'sults-avatar' ) ); ?>
                        </td>

                        <td>
                            <div class="sults-download-actions" style="justify-content: center;">
                                <a href="#" class="sults-icon-btn" title="Abrir Externo">
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                                <a href="#" class="sults-icon-btn" title="Baixar ZIP">
                                    <span class="dashicons dashicons-download"></span>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <?php
        $big = 999999999;
        echo '<div style="margin-top: 20px; display: flex; justify-content: flex-end;">';
        echo paginate_links( array(
            'base'    => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
            'format'  => '?paged=%#%',
            'current' => max( 1, $filters['paged'] ),
            'total'   => $query->max_num_pages,
            'prev_text' => '&lsaquo;',
            'next_text' => '&rsaquo;',
        ) );
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