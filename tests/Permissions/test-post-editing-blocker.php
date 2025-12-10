<?php
/**
 * Testes unitários para a classe PostEditingBlocker.
 */

use Sults\Writen\Workflow\Permissions\PostEditingBlocker;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;
use Sults\Writen\Infrastructure\RequestBlocker; 

class Test_Post_Editing_Blocker extends WP_UnitTestCase {

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_bloqueia_edicao_se_status_e_role_match() {
		$mockUser    = Mockery::mock( WPUserProviderInterface::class );
		$mockStatus  = Mockery::mock( WPPostStatusProviderInterface::class );
		$mockRequest = Mockery::mock( RequestBlocker::class ); 

		$postId = 123;
		
		$mockStatus->shouldReceive( 'get_status' )->with( $postId )->andReturn( 'suspended' );
		$mockUser->shouldReceive( 'get_current_user_roles' )->andReturn( array( 'contributor' ) );
		
		// Simulamos que o método É POST através do objeto, não da global
		$mockRequest->shouldReceive( 'is_post_method' )->once()->andReturn( true );

		// Passamos o 3º argumento
		$blocker = new PostEditingBlocker( $mockUser, $mockStatus, $mockRequest );
		
		$caps = array( 'edit_post' );
		$args = array( $postId ); 
		
		$result = $blocker->filter_map_meta_cap( $caps, 'edit_post', 1, $args );

		$this->assertEquals( array( 'do_not_allow' ), $result );
	}

	public function test_permite_leitura_se_status_e_role_match_mas_metodo_for_get() {
		$mockUser    = Mockery::mock( WPUserProviderInterface::class );
		$mockStatus  = Mockery::mock( WPPostStatusProviderInterface::class );
		$mockRequest = Mockery::mock( RequestBlocker::class );

		$postId = 123;
		$mockStatus->shouldReceive( 'get_status' )->with( $postId )->andReturn( 'suspended' );
		$mockUser->shouldReceive( 'get_current_user_roles' )->andReturn( array( 'contributor' ) );

		// Simulamos que o método NÃO É POST (é GET/Visualização)
		$mockRequest->shouldReceive( 'is_post_method' )->once()->andReturn( false );

		$blocker = new PostEditingBlocker( $mockUser, $mockStatus, $mockRequest );
		
		$caps = array( 'edit_post' );
		$args = array( $postId ); 
		
		$result = $blocker->filter_map_meta_cap( $caps, 'edit_post', 1, $args );

		$this->assertEquals( $caps, $result );
	}

	public function test_permite_edicao_status_livre() {
		$mockUser    = Mockery::mock( WPUserProviderInterface::class );
		$mockStatus  = Mockery::mock( WPPostStatusProviderInterface::class );
		$mockRequest = Mockery::mock( RequestBlocker::class );

		$postId = 123;
		$mockStatus->shouldReceive( 'get_status' )->with( $postId )->andReturn( 'draft' );
		
		// Se o status é livre, o código nem chega a verificar se é POST
		// Então não definimos expectativa para $mockRequest, ou permitimos zero chamadas.
		
		$blocker = new PostEditingBlocker( $mockUser, $mockStatus, $mockRequest );
		
		$caps = array( 'edit_post' );
		
		$result = $blocker->filter_map_meta_cap( $caps, 'edit_post', 1, array( $postId ) );

		$this->assertEquals( $caps, $result );
	}

	public function test_deve_bloquear_edicao_se_status_e_role_estiverem_na_lista_negra() {
		$mockUser    = Mockery::mock( WPUserProviderInterface::class );
		$mockStatus  = Mockery::mock( WPPostStatusProviderInterface::class );
		$mockRequest = Mockery::mock( RequestBlocker::class );

		$postId = 10;
		$mockStatus->shouldReceive( 'get_status' )->with( $postId )->andReturn( 'suspended' );
		$mockUser->shouldReceive( 'get_current_user_roles' )->andReturn( array( 'contributor' ) );
		
		// É POST, deve bloquear
		$mockRequest->shouldReceive( 'is_post_method' )->andReturn( true );

		$blocker = new PostEditingBlocker( $mockUser, $mockStatus, $mockRequest );

		$caps = array( 'edit_post' ); 
		
		$result = $blocker->filter_map_meta_cap( $caps, 'edit_post', 1, array( $postId ) );

		$this->assertEquals( array( 'do_not_allow' ), $result );
	}

	public function test_deve_permitir_edicao_se_status_for_livre() {
		$mockUser    = Mockery::mock( WPUserProviderInterface::class );
		$mockStatus  = Mockery::mock( WPPostStatusProviderInterface::class );
		$mockRequest = Mockery::mock( RequestBlocker::class );

		$mockStatus->shouldReceive( 'get_status' )->andReturn( 'draft' );
		$mockUser->shouldReceive( 'get_current_user_roles' )->never(); 

		$blocker = new PostEditingBlocker( $mockUser, $mockStatus, $mockRequest );

		$caps = array( 'edit_post' );
		
		$result = $blocker->filter_map_meta_cap( $caps, 'edit_post', 1, array( 10 ) );

		$this->assertEquals( $caps, $result );
	}

	public function test_deve_permitir_edicao_se_usuario_for_admin() {
		$mockUser    = Mockery::mock( WPUserProviderInterface::class );
		$mockStatus  = Mockery::mock( WPPostStatusProviderInterface::class );
		$mockRequest = Mockery::mock( RequestBlocker::class );

		$mockStatus->shouldReceive( 'get_status' )->andReturn( 'suspended' );
		$mockUser->shouldReceive( 'get_current_user_roles' )->andReturn( array( 'administrator' ) );

		$blocker = new PostEditingBlocker( $mockUser, $mockStatus, $mockRequest );

		$caps = array( 'edit_post' );

		$result = $blocker->filter_map_meta_cap( $caps, 'edit_post', 1, array( 10 ) );

		$this->assertEquals( $caps, $result );
	}
}