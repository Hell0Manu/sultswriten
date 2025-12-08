<?php

use Sults\Writen\Workflow\Permissions\DeletePrevention;
use Sults\Writen\Workflow\Permissions\PostListVisibility;
use Sults\Writen\Workflow\Permissions\RoleCapabilityManager;
use Sults\Writen\Contracts\WPUserProviderInterface;

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
        $mockUserProvider = Mockery::mock( WPUserProviderInterface::class );
        $mockUserProvider->shouldReceive( 'get_current_user_roles' )
            ->andReturn( array( 'contributor' ) );

        $visibility = new PostListVisibility( $mockUserProvider );

        // Simula WP_Query na administração principal
        $query = Mockery::mock( \WP_Query::class );
        $query->shouldReceive( 'is_main_query' )->andReturn( true );
        $query->shouldReceive( 'get' )->with( 'post_type' )->andReturn( 'post' );

        // Mock global wpdb e funções de usuário
        global $wpdb, $current_user;
        $user_id = get_current_user_id(); // ID atual do teste (provavelmente 0 ou admin se não setado)
        
        // Força is_admin() para true no contexto do teste (WP_UnitTestCase já lida bem, mas setamos o current screen se necessário)
        set_current_screen( 'edit.php' ); 

        $where_clause = "AND 1=1";
        $result = $visibility->restrict_post_list_visibility( $where_clause, $query );

        // Verifica se a cláusula SQL foi injetada
        $this->assertStringContainsString( "{$wpdb->posts}.post_author =", $result );
        $this->assertStringContainsString( "post_status IN", $result );
    }

    public function test_visibilidade_nao_afeta_outras_roles() {
        $mockUserProvider = Mockery::mock( WPUserProviderInterface::class );
        $mockUserProvider->shouldReceive( 'get_current_user_roles' )
            ->andReturn( array( 'administrator' ) );

        $visibility = new PostListVisibility( $mockUserProvider );

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