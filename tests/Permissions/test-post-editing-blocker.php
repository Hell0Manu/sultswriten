<?php
/**
 * Testes unitários para a classe PostEditingBlocker.
 *
 * Verifica se as regras de bloqueio de edição (baseadas em status e função do usuário)
 * estão sendo aplicadas corretamente ao filtrar as capacidades do WordPress (map_meta_cap).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Tests\Workflow\Permissions
 * @since      0.1.0
 */

use Sults\Writen\Workflow\Permissions\PostEditingBlocker;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;

class Test_Post_Editing_Blocker extends WP_UnitTestCase {

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_bloqueia_edicao_se_status_e_role_match() {
        $mockUser   = Mockery::mock( WPUserProviderInterface::class );
        $mockStatus = Mockery::mock( WPPostStatusProviderInterface::class );

        $postId = 123;
        
        $mockStatus->shouldReceive( 'get_status' )->with( $postId )->andReturn( 'suspended' );
        $mockUser->shouldReceive( 'get_current_user_roles' )->andReturn( array( 'contributor' ) );

        $blocker = new PostEditingBlocker( $mockUser, $mockStatus );

        // A classe usa filters para definir quais status/roles bloquear.
        // O padrão é bloquear 'suspended' para 'contributor'.
        
        $caps = array( 'edit_post' );
        $args = array( $postId ); // args[0] é o post_id
        
        $result = $blocker->filter_map_meta_cap( $caps, 'edit_post', 1, $args );

        $this->assertEquals( array( 'do_not_allow' ), $result );
    }

	public function test_permite_edicao_status_livre() {
        $mockUser   = Mockery::mock( WPUserProviderInterface::class );
        $mockStatus = Mockery::mock( WPPostStatusProviderInterface::class );

        $postId = 123;
        $mockStatus->shouldReceive( 'get_status' )->with( $postId )->andReturn( 'draft' );
        // Se o status não está na lista de bloqueio, ele nem checa a role
        
        $blocker = new PostEditingBlocker( $mockUser, $mockStatus );
        
        $caps = array( 'edit_post' );
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
		$result = $blocker->filter_map_meta_cap( $caps, 'edit_post', 1, array( $postId ) );

		$this->assertEquals( array( 'do_not_allow' ), $result );
	}

	public function test_deve_permitir_edicao_se_status_for_livre() {
		$mockUser   = Mockery::mock( WPUserProviderInterface::class );
		$mockStatus = Mockery::mock( WPPostStatusProviderInterface::class );

		$mockStatus->shouldReceive( 'get_status' )->andReturn( 'draft' );
		
		$mockUser->shouldReceive( 'get_current_user_roles' )->never(); // Nem precisa checar role se o status é livre

		$blocker = new PostEditingBlocker( $mockUser, $mockStatus );

		$caps = array( 'edit_post' );
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
		$result = $blocker->filter_map_meta_cap( $caps, 'edit_post', 1, array( 10 ) );

		$this->assertEquals( $caps, $result );
	}
}