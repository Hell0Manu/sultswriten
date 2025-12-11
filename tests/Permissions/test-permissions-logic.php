<?php

use Sults\Writen\Workflow\Permissions\DeletePrevention;
use Sults\Writen\Workflow\Permissions\PostListVisibility;
use Sults\Writen\Workflow\Permissions\RoleCapabilityManager;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Workflow\Permissions\VisibilityPolicy;

class Test_Permissions_Logic extends WP_UnitTestCase {

    protected function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }

    // --- DeletePrevention ---

    public function test_delete_prevention_bloqueia_editor_de_deletar_lixo() {
        $prevention = new DeletePrevention();
        
        // Cria um post na lixeira
        $post_id = $this->factory->post->create( array( 'post_status' => 'trash' ) );
        
        // Simula usuário Editor
        $user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
        
        // Testa map_meta_cap
        $caps = array( 'delete_post' );
        $args = array( $post_id );
        
        // Como o método usa get_userdata( $user_id ), precisamos passar o ID correto
        $result = $prevention->prevent_permanent_delete( $caps, 'delete_post', $user_id, $args );

        $this->assertEquals( array( 'do_not_allow' ), $result );
    }

    public function test_delete_prevention_permite_admin() {
        $prevention = new DeletePrevention();
        $post_id = $this->factory->post->create( array( 'post_status' => 'trash' ) );
        $user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
        
        $caps = array( 'delete_post' );
        $result = $prevention->prevent_permanent_delete( $caps, 'delete_post', $user_id, array( $post_id ) );

        $this->assertEquals( $caps, $result );
    }

    // --- PostListVisibility ---

public function test_visibilidade_restrita_para_contributor() {
        // 1. Simula que estamos na tela de listagem de posts do Admin
        set_current_screen( 'edit.php' ); 

        // 2. Prepara os Mocks
        $mockUserProvider = Mockery::mock( WPUserProviderInterface::class );
        $mockUserProvider->shouldReceive( 'get_current_user_roles' )
            ->andReturn( array( 'contributor' ) );

        // 3. Instancia as classes reais
        $policy = new VisibilityPolicy( $mockUserProvider );
        $visibility = new PostListVisibility( $policy );

        // 4. Mock da Query
        $query = Mockery::mock( \WP_Query::class );
        $query->shouldReceive( 'is_main_query' )->andReturn( true );
        $query->shouldReceive( 'get' )->with( 'post_type' )->andReturn( 'post' );

        // 5. Executa
        global $wpdb;
        $where_clause = "AND 1=1";
        $result = $visibility->restrict_post_list_visibility( $where_clause, $query );

        // 6. Verifica
        $this->assertStringContainsString( "post_author =", $result );
        $this->assertStringContainsString( "post_status IN", $result );
    }

    public function test_visibilidade_nao_afeta_outras_roles() {
        $mockUserProvider = Mockery::mock( WPUserProviderInterface::class );
        $mockUserProvider->shouldReceive( 'get_current_user_roles' )
            ->andReturn( array( 'administrator' ) );

        // CORREÇÃO AQUI TAMBÉM
        $policy = new VisibilityPolicy( $mockUserProvider );
        $visibility = new PostListVisibility( $policy );

        $query = Mockery::mock( \WP_Query::class );
        $query->shouldReceive( 'is_main_query' )->andReturn( true );
        $query->shouldReceive( 'get' )->with( 'post_type' )->andReturn( 'post' );

        $where_original = "AND 1=1";
        $result = $visibility->restrict_post_list_visibility( $where_original, $query );

        $this->assertEquals( $where_original, $result );
    }

    // --- RoleCapabilityManager ---

    public function test_role_manager_aplica_e_reverte_caps() {
        $manager = new RoleCapabilityManager();

        // 1. Aplica
        $manager->apply();
        $this->assertTrue( get_role( 'contributor' )->has_cap( 'upload_files' ), 'Contributor deveria ter upload_files' );
        $this->assertFalse( get_role( 'editor' )->has_cap( 'delete_published_pages' ), 'Editor não deveria deletar páginas publicadas' );

        // 2. Reverte
        $manager->revert();
        $this->assertFalse( get_role( 'contributor' )->has_cap( 'upload_files' ), 'Reversão falhou para contributor' );
        // O WP padrão permite editores deletarem paginas, então deve voltar a ser true
        $this->assertTrue( get_role( 'editor' )->has_cap( 'delete_published_pages' ), 'Reversão falhou para editor' );
    }
}