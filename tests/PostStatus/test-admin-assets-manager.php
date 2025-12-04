<?php

use Sults\Writen\Workflow\PostStatus\AdminAssetsManager;
use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Contracts\WPUserProviderInterface;

class Test_AdminAssetsManager extends WP_UnitTestCase {

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_nao_deve_carregar_scripts_em_paginas_irrelevantes() {
		$mockLoader = Mockery::mock( AssetLoaderInterface::class );
		$mockUser   = Mockery::mock( WPUserProviderInterface::class );

		$mockLoader->shouldReceive( 'enqueue_script' )->never();
		$mockLoader->shouldReceive( 'enqueue_style' )->never();

		$manager = new AdminAssetsManager( 'http://url.com/', '1.0', $mockLoader, $mockUser );

		$manager->enqueue_scripts( 'index.php' );

		$this->assertTrue( true );
	}

	public function test_deve_carregar_scripts_na_tela_de_edicao() {
		$mockLoader = Mockery::mock( AssetLoaderInterface::class );
		$mockUser   = Mockery::mock( WPUserProviderInterface::class );
		$mockUser->shouldReceive( 'get_current_user_roles' )
			->once()
			->andReturn( array( 'administrator' ) );

		$mockLoader->shouldReceive( 'enqueue_style' )
			->withArgs(
				function ( $handle ) {
					return $handle === 'sultswriten-variables-css';
				}
			)->once();

		$mockLoader->shouldReceive( 'enqueue_style' )
			->withArgs(
				function ( $handle ) {
					return $handle === 'sultswriten-status-css';
				}
			)->once();

		$mockLoader->shouldReceive( 'enqueue_script' )
			->once();

		$mockLoader->shouldReceive( 'localize_script' )
			->with( 'sultswriten-statuses', 'SultsWritenStatuses', Mockery::type( 'array' ) )
			->once();

		$manager = new AdminAssetsManager( 'http://url.com/', '1.0', $mockLoader, $mockUser );

		$manager->enqueue_scripts( 'post.php' );

		$this->assertTrue( true );
	}
}