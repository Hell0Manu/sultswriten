<?php

use Sults\Writen\Workflow\PostStatus\AdminAssetsManager;
use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Infrastructure\AssetPathResolver;

class Test_AdminAssetsManager extends WP_UnitTestCase {

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_nao_deve_carregar_scripts_em_paginas_irrelevantes() {
		$mockLoader   = Mockery::mock( AssetLoaderInterface::class );
		$mockUser     = Mockery::mock( WPUserProviderInterface::class );
		$mockResolver = Mockery::mock( AssetPathResolver::class );

		$mockLoader->shouldReceive( 'enqueue_script' )->never();
		$mockLoader->shouldReceive( 'enqueue_style' )->never();
		// Garante que o inline style também não seja chamado
		$mockLoader->shouldReceive( 'add_inline_style' )->never();

		$manager = new AdminAssetsManager( $mockLoader, $mockUser, $mockResolver );

		$manager->enqueue_scripts( 'index.php' );

		$this->assertTrue( true );
	}

	public function test_deve_carregar_scripts_na_tela_de_edicao() {
		$mockLoader   = Mockery::mock( AssetLoaderInterface::class );
		$mockUser     = Mockery::mock( WPUserProviderInterface::class );
		$mockResolver = Mockery::mock( AssetPathResolver::class );

		$mockUser->shouldReceive( 'get_current_user_roles' )
			->once()
			->andReturn( array( 'administrator' ) );

		$mockResolver->shouldReceive('get_version')->andReturn('1.0');
		$mockResolver->shouldReceive('get_css_url')->andReturn('http://teste.com/style.css');
		$mockResolver->shouldReceive('get_js_url')->andReturn('http://teste.com/script.js');

		$mockLoader->shouldReceive( 'enqueue_style' )->times(2);
		$mockLoader->shouldReceive( 'enqueue_script' )->once();

		$mockLoader->shouldReceive( 'add_inline_style' )
			->once()
			// Verifica se o handle está correto e se o segundo argumento é uma string (o CSS)
			->with( 'sultswriten-status-css', Mockery::type('string') );
		// -------------------------------------------------------------

		$mockLoader->shouldReceive( 'localize_script' )
			->with( 'sultswriten-statuses', 'SultsWritenStatuses', Mockery::type( 'array' ) )
			->once();

		$manager = new AdminAssetsManager( $mockLoader, $mockUser, $mockResolver );

		$manager->enqueue_scripts( 'post.php' );

		$this->assertTrue( true );
	}
}