<?php

use Sults\Writen\Integrations\AIOSEO\AIOSEOCleaner;
use Sults\Writen\Contracts\WPUserProviderInterface;

class Test_AIOSEO_Cleaner extends WP_UnitTestCase {

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_deve_identificar_usuario_restrito_corretamente() {
		$mockUser = Mockery::mock( WPUserProviderInterface::class );
		
		// Simula um "Redator" (contributor)
		$mockUser->shouldReceive( 'get_current_user_roles' )
			->once()
			->andReturn( array( 'contributor' ) );

		$cleaner = new AIOSEOCleaner( $mockUser );

		$this->assertTrue( $cleaner->is_restricted_user(), 'Contributor deve ser considerado usuário restrito.' );
	}

	public function test_nao_deve_restringir_administrador() {
		$mockUser = Mockery::mock( WPUserProviderInterface::class );
		
		// Simula Admin
		$mockUser->shouldReceive( 'get_current_user_roles' )
			->once()
			->andReturn( array( 'administrator' ) );

		$cleaner = new AIOSEOCleaner( $mockUser );

		$this->assertFalse( $cleaner->is_restricted_user(), 'Administrador NÃO deve ser restrito.' );
	}
}