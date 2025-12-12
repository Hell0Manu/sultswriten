<?php
use Sults\Writen\Workflow\Permissions\PostEditingBlocker;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;
use Sults\Writen\Contracts\RequestProviderInterface;
use Sults\Writen\Workflow\WorkflowPolicy;

class Test_Post_Editing_Blocker extends WP_UnitTestCase {

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_bloqueia_edicao_se_status_e_role_match() {
		$mockUser    = Mockery::mock( WPUserProviderInterface::class );
		$mockStatus  = Mockery::mock( WPPostStatusProviderInterface::class );
		$mockRequest = Mockery::mock( RequestProviderInterface::class );
		$mockPolicy  = Mockery::mock( WorkflowPolicy::class ); // <--- NOVO

		$postId = 123;
		
		$mockStatus->shouldReceive( 'get_status' )->with( $postId )->andReturn( 'suspended' );
		$mockUser->shouldReceive( 'get_current_user_roles' )->andReturn( array( 'contributor' ) );
		
		// A Policy diz que ESTÁ travado
		$mockPolicy->shouldReceive( 'is_editing_locked' )
			->with( 'suspended', array( 'contributor' ) )
			->andReturn( true );

		// É POST
		$mockRequest->shouldReceive( 'is_post_method' )->once()->andReturn( true );

		$blocker = new PostEditingBlocker( $mockUser, $mockStatus, $mockRequest, $mockPolicy ); // <--- 4 argumentos
		
		$caps = array( 'edit_post' );
		$args = array( $postId ); 
		
		$result = $blocker->filter_map_meta_cap( $caps, 'edit_post', 1, $args );

		$this->assertEquals( array( 'do_not_allow' ), $result );
	}

	public function test_permite_leitura_se_status_e_role_match_mas_metodo_for_get() {
		$mockUser    = Mockery::mock( WPUserProviderInterface::class );
		$mockStatus  = Mockery::mock( WPPostStatusProviderInterface::class );
		$mockRequest = Mockery::mock( RequestProviderInterface::class );
		$mockPolicy  = Mockery::mock( WorkflowPolicy::class );

		$postId = 123;
		$mockStatus->shouldReceive( 'get_status' )->with( $postId )->andReturn( 'suspended' );
		$mockUser->shouldReceive( 'get_current_user_roles' )->andReturn( array( 'contributor' ) );

		// A Policy diz que ESTÁ travado
		$mockPolicy->shouldReceive( 'is_editing_locked' )->andReturn( true );

		// Mas NÃO É POST
		$mockRequest->shouldReceive( 'is_post_method' )->once()->andReturn( false );

		$blocker = new PostEditingBlocker( $mockUser, $mockStatus, $mockRequest, $mockPolicy );
		
		$caps = array( 'edit_post' );
		$args = array( $postId ); 
		
		$result = $blocker->filter_map_meta_cap( $caps, 'edit_post', 1, $args );

		$this->assertEquals( $caps, $result );
	}

	public function test_permite_edicao_status_livre() {
		$mockUser    = Mockery::mock( WPUserProviderInterface::class );
		$mockStatus  = Mockery::mock( WPPostStatusProviderInterface::class );
		$mockRequest = Mockery::mock( RequestProviderInterface::class );
		$mockPolicy  = Mockery::mock( WorkflowPolicy::class );

		$postId = 123;
		$mockStatus->shouldReceive( 'get_status' )->with( $postId )->andReturn( 'draft' );
		$mockUser->shouldReceive( 'get_current_user_roles' )->andReturn( array( 'contributor' ) ); // Adicionado para satisfazer a chamada

		// A Policy diz que NÃO está travado
		$mockPolicy->shouldReceive( 'is_editing_locked' )
			->andReturn( false );
		
		$blocker = new PostEditingBlocker( $mockUser, $mockStatus, $mockRequest, $mockPolicy );
		
		$caps = array( 'edit_post' );
		
		$result = $blocker->filter_map_meta_cap( $caps, 'edit_post', 1, array( $postId ) );

		$this->assertEquals( $caps, $result );
	}

	public function test_deve_bloquear_edicao_se_status_e_role_estiverem_na_lista_negra() {
		$mockUser    = Mockery::mock( WPUserProviderInterface::class );
		$mockStatus  = Mockery::mock( WPPostStatusProviderInterface::class );
		$mockRequest = Mockery::mock( RequestProviderInterface::class );
		$mockPolicy  = Mockery::mock( WorkflowPolicy::class );

		$postId = 10;
		$mockStatus->shouldReceive( 'get_status' )->with( $postId )->andReturn( 'suspended' );
		$mockUser->shouldReceive( 'get_current_user_roles' )->andReturn( array( 'contributor' ) );
		
		// Policy confirma bloqueio
		$mockPolicy->shouldReceive( 'is_editing_locked' )->andReturn( true );

		// É POST
		$mockRequest->shouldReceive( 'is_post_method' )->andReturn( true );

		$blocker = new PostEditingBlocker( $mockUser, $mockStatus, $mockRequest, $mockPolicy );

		$caps = array( 'edit_post' ); 
		
		$result = $blocker->filter_map_meta_cap( $caps, 'edit_post', 1, array( $postId ) );

		$this->assertEquals( array( 'do_not_allow' ), $result );
	}

	public function test_deve_permitir_edicao_se_usuario_for_admin() {
		$mockUser    = Mockery::mock( WPUserProviderInterface::class );
		$mockStatus  = Mockery::mock( WPPostStatusProviderInterface::class );
		$mockRequest = Mockery::mock( RequestProviderInterface::class );
		$mockPolicy  = Mockery::mock( WorkflowPolicy::class );

		$mockStatus->shouldReceive( 'get_status' )->andReturn( 'suspended' );
		$mockUser->shouldReceive( 'get_current_user_roles' )->andReturn( array( 'administrator' ) );

		// Policy diz que admin não é bloqueado
		$mockPolicy->shouldReceive( 'is_editing_locked' )
			->with( 'suspended', array('administrator') )
			->andReturn( false );

		$blocker = new PostEditingBlocker( $mockUser, $mockStatus, $mockRequest, $mockPolicy );

		$caps = array( 'edit_post' );

		$result = $blocker->filter_map_meta_cap( $caps, 'edit_post', 1, array( 10 ) );

		$this->assertEquals( $caps, $result );
	}
}