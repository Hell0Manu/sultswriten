<?php

use Sults\Writen\Workflow\PostStatus\PostListPresenter;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;
use Sults\Writen\Contracts\WPUserProviderInterface;

class Test_PostListPresenter extends WP_UnitTestCase {

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_deve_renderizar_html_correto_para_status_suspenso() {
		$mockStatusProvider = Mockery::mock( WPPostStatusProviderInterface::class );
		$mockUserProvider   = Mockery::mock( WPUserProviderInterface::class );

		$sults_postId      = 123;
		$statusSlug  = 'suspended';
		$statusLabel = 'Suspenso';

		$mockStatusProvider->shouldReceive( 'get_status' )
			->with( $sults_postId )
			->andReturn( $statusSlug );

		$statusObj = (object) array( 'label' => $statusLabel );
		$mockStatusProvider->shouldReceive( 'get_status_object' )
			->with( $statusSlug )
			->andReturn( $statusObj );

		$sults_presenter = new PostListPresenter( $mockStatusProvider, $mockUserProvider );

		$expectedHtml = '<span class="sults-status-badge sults-status-suspended">Suspenso</span>';
		$this->expectOutputString( $expectedHtml );

		$sults_presenter->fill_status_column_content( 'post_status_custom', $sults_postId );
	}

	public function test_nao_deve_fazer_nada_se_coluna_for_incorreta() {
		$mockStatus = Mockery::mock( WPPostStatusProviderInterface::class );
		$mockUser   = Mockery::mock( WPUserProviderInterface::class );

		$mockStatus->shouldReceive( 'get_status' )->never();

		$sults_presenter = new PostListPresenter( $mockStatus, $mockUser );
		$sults_presenter->fill_status_column_content( 'author', 123 );

		$this->assertTrue( true );
	}
}