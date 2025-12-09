<?php
/**
 * Testes unitários para a classe PostEditingBlocker.
 */

use Sults\Writen\Workflow\Permissions\PostEditingBlocker;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;

class Test_Post_Editing_Blocker extends WP_UnitTestCase {

	protected function tearDown(): void {
		Mockery::close();
		// Limpar a global após o teste para não afetar outros
		unset( $_SERVER['REQUEST_METHOD'] ); 
		parent::tearDown();
	}

	public function test_bloqueia_edicao_se_status_e_role_match() {
		$mockUser   = Mockery::mock( WPUserProviderInterface::class );
		$mockStatus = Mockery::mock( WPPostStatusProviderInterface::class );

		$postId = 123;
		
		$mockStatus->shouldReceive( 'get_status' )->with( $postId )->andReturn( 'suspended' );
		$mockUser->shouldReceive( 'get_current_user_roles' )->andReturn( array( 'contributor' ) );

		$blocker = new PostEditingBlocker( $mockUser, $mockStatus );
		
		$caps = array( 'edit_post' );
		$args = array( $postId ); 
		
		// --- CORREÇÃO: Simular requisição POST ---
		$_SERVER['REQUEST_METHOD'] = 'POST';
		
		$result = $blocker->filter_map_meta_cap( $caps, 'edit_post', 1, $args );

		$this->assertEquals( array( 'do_not_allow' ), $result );
	}

	public function test_permite_leitura_se_status_e_role_match_mas_metodo_for_get() {
		// NOVO TESTE: Garante que o modo "Apenas Leitura" funciona
		$mockUser   = Mockery::mock( WPUserProviderInterface::class );
		$mockStatus = Mockery::mock( WPPostStatusProviderInterface::class );

		$postId = 123;
		$mockStatus->shouldReceive( 'get_status' )->with( $postId )->andReturn( 'suspended' );
		$mockUser->shouldReceive( 'get_current_user_roles' )->andReturn( array( 'contributor' ) );

		$blocker = new PostEditingBlocker( $mockUser, $mockStatus );
		
		$caps = array( 'edit_post' );
		$args = array( $postId ); 
		
		// Simular requisição GET (apenas visualização)
		$_SERVER['REQUEST_METHOD'] = 'GET';
		
		$result = $blocker->filter_map_meta_cap( $caps, 'edit_post', 1, $args );

		// Deve retornar as capabilities originais (permitir ver), não bloquear
		$this->assertEquals( $caps, $result );
	}

	public function test_permite_edicao_status_livre() {
		$mockUser   = Mockery::mock( WPUserProviderInterface::class );
		$mockStatus = Mockery::mock( WPPostStatusProviderInterface::class );

		$postId = 123;
		$mockStatus->shouldReceive( 'get_status' )->with( $postId )->andReturn( 'draft' );
		
		$blocker = new PostEditingBlocker( $mockUser, $mockStatus );
		
		$caps = array( 'edit_post' );
		
		// Mesmo sendo POST, status livre libera
		$_SERVER['REQUEST_METHOD'] = 'POST';
		
		$result = $blocker->filter_map_meta_cap( $caps, 'edit_post', 1, array( $postId ) );

		$this->assertEquals( $caps, $result );
	}

	public function test_deve_bloquear_edicao_se_status_e_role_estiverem_na_lista_negra() {
		$mockUser   = Mockery::mock( WPUserProviderInterface::class );
		$mockStatus = Mockery::mock( WPPostStatusProviderInterface::class );

		$postId = 10;
		$mockStatus->shouldReceive( 'get_status' )
			->with( $postId )
			->andReturn( 'suspended' );

		$mockUser->shouldReceive( 'get_current_user_roles' )
			->andReturn( array( 'contributor' ) );

		$blocker = new PostEditingBlocker( $mockUser, $mockStatus );

		$caps = array( 'edit_post' ); 
		
		// --- CORREÇÃO: Simular requisição POST ---
		$_SERVER['REQUEST_METHOD'] = 'POST';

		$result = $blocker->filter_map_meta_cap( $caps, 'edit_post', 1, array( $postId ) );

		$this->assertEquals( array( 'do_not_allow' ), $result );
	}

	public function test_deve_permitir_edicao_se_status_for_livre() {
		$mockUser   = Mockery::mock( WPUserProviderInterface::class );
		$mockStatus = Mockery::mock( WPPostStatusProviderInterface::class );

		$mockStatus->shouldReceive( 'get_status' )->andReturn( 'draft' );
		$mockUser->shouldReceive( 'get_current_user_roles' )->never(); 

		$blocker = new PostEditingBlocker( $mockUser, $mockStatus );

		$caps = array( 'edit_post' );
		$_SERVER['REQUEST_METHOD'] = 'POST';

		$result = $blocker->filter_map_meta_cap( $caps, 'edit_post', 1, array( 10 ) );

		$this->assertEquals( $caps, $result );
	}

	public function test_deve_permitir_edicao_se_usuario_for_admin() {
		$mockUser   = Mockery::mock( WPUserProviderInterface::class );
		$mockStatus = Mockery::mock( WPPostStatusProviderInterface::class );

		$mockStatus->shouldReceive( 'get_status' )->andReturn( 'suspended' );
		$mockUser->shouldReceive( 'get_current_user_roles' )->andReturn( array( 'administrator' ) );

		$blocker = new PostEditingBlocker( $mockUser, $mockStatus );

		$caps = array( 'edit_post' );
		$_SERVER['REQUEST_METHOD'] = 'POST';

		$result = $blocker->filter_map_meta_cap( $caps, 'edit_post', 1, array( 10 ) );

		$this->assertEquals( $caps, $result );
	}
}