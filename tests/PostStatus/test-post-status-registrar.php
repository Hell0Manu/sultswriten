<?php

use Sults\Writen\Workflow\PostStatus\PostStatusRegistrar;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;

class Test_PostStatusRegistrar extends WP_UnitTestCase {

	protected function tearDown(): void {
		Mockery::close(); 
		parent::tearDown();
	}

	public function test_deve_registrar_todos_os_status_customizados() {
		$mockProvider = Mockery::mock( WPPostStatusProviderInterface::class );

		$statuses       = PostStatusRegistrar::CUSTOM_STATUSES;
		$total_statuses = count( $statuses );

		$mockProvider->shouldReceive( 'register' )
			->times( $total_statuses )
			->andReturn( (object) array() );

		$registrar = new PostStatusRegistrar( $mockProvider );
		$registrar->register_custom_statuses();

		$this->assertTrue( true );
	}
}
