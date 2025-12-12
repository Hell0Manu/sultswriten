<?php

use Sults\Writen\Workflow\PostStatus\PostStatusRegistrar;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;
use Sults\Writen\Workflow\PostStatus\StatusConfig;

class Test_PostStatusRegistrar extends WP_UnitTestCase {

	protected function tearDown(): void {
		Mockery::close(); 
		parent::tearDown();
	}

	public function test_deve_registrar_todos_os_status_customizados() {
		$mockProvider = Mockery::mock( WPPostStatusProviderInterface::class );

		// Correção: Usar StatusConfig em vez da constante removida
		$statuses       = StatusConfig::get_all();
		$total_statuses = count( $statuses );

		$mockProvider->shouldReceive( 'register' )
			->times( $total_statuses )
			->andReturn( (object) array() );

		$registrar = new PostStatusRegistrar( $mockProvider );
		$registrar->register_custom_statuses();

		$this->assertTrue( true );
	}
}