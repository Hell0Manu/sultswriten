<?php
namespace Sults\Writen\Interface\Dashboard;

use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Contracts\PostRepositoryInterface;
use Sults\Writen\Contracts\WPUserProviderInterface;

class ExportController implements HookableInterface {

    private PostRepositoryInterface $post_repo;
    private WPUserProviderInterface $user_provider;

    public const PAGE_SLUG = 'sults-writen-export';

    public function __construct(
        PostRepositoryInterface $post_repo,
        WPUserProviderInterface $user_provider,
    ) {
        $this->post_repo     = $post_repo;
        $this->user_provider = $user_provider;
    }

    public function register(): void {
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
    }

    public function add_menu_page(): void {
        add_menu_page(
            'Sults Export',      
            'Sults Export',      
            'manage_options',      
            self::PAGE_SLUG,     
            array( $this, 'render' ),
            'dashicons-download', 
            3                   
        );
    }

    public function render(): void {
        $filters = array(
            's'      => isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '',
            'author' => isset( $_GET['author'] ) ? absint( $_GET['author'] ) : '',
            'cat'    => isset( $_GET['cat'] ) ? absint( $_GET['cat'] ) : '',
            'paged'  => isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1,
        );

        $query = $this->post_repo->get_finished_posts( $filters );

        $cat_dropdown_args = array(
                'show_option_all' => 'Categorias',
                'name'            => 'cat',
                'selected'        => $filters['cat'],
                'echo'            => 0,
                'hierarchical'    => true,
                'class'           => 'sults-filter-select', 
            );
        $categories_dropdown = wp_dropdown_categories( $cat_dropdown_args );

        $author_dropdown = $this->user_provider->get_users_dropdown( array(
            'show_option_all' => 'Autores',
            'name'            => 'author',
            'selected'        => $filters['author'],
            'who'             => 'authors',
            'class'           => 'sults-filter-select',
        ) );

        require __DIR__ . '/views/export-home.php';
    }
}