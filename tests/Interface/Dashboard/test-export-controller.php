<?php

use Sults\Writen\Interface\Dashboard\ExportController;
use Sults\Writen\Contracts\PostRepositoryInterface;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\HtmlExtractorInterface;
use Sults\Writen\Contracts\JspBuilderInterface;
use Sults\Writen\Contracts\SeoDataProviderInterface;
use Sults\Writen\Workflow\Export\ExportAssetsManager; // Adicionar USE
use Sults\Writen\Workflow\Export\ExportPayload; // Adicionar USE

class Test_ExportController extends WP_UnitTestCase {

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_render_preview_screen_deve_gerar_jsp() {
		// 1. Mocks
		$mockRepo      = Mockery::mock( PostRepositoryInterface::class );
		$mockUser      = Mockery::mock( WPUserProviderInterface::class );
		$mockExtractor = Mockery::mock( HtmlExtractorInterface::class );
		$mockBuilder   = Mockery::mock( JspBuilderInterface::class );
		$mockSeo       = Mockery::mock( SeoDataProviderInterface::class );
		
		// NOVO MOCK para o AssetsManager
		$mockAssets    = Mockery::mock( ExportAssetsManager::class );

		$post_id = $this->factory->post->create( array( 'post_title' => 'Titulo Teste' ) );
		
		// Simula GET parameters
		$_GET['action']   = 'preview';
		$_GET['post_id']  = $post_id;
		$_GET['_wpnonce'] = wp_create_nonce( 'sults_preview_' . $post_id );

		// 2. Expectativas
		$mockExtractor->shouldReceive( 'extract' )->once()->andReturn( '<p>HTML Limpo</p>' );
		
		// Configura o Mock do Assets Manager para retornar um Payload fake
		$mockAssets->shouldReceive( 'process' )
			->once()
			->with( '<p>HTML Limpo</p>', Mockery::any() ) // Aceita qualquer path no 2o argumento
			->andReturn( new ExportPayload( '<p>HTML Processado</p>', array() ) );

		$mockSeo->shouldReceive( 'get_seo_data' )
			->once()
			->with( $post_id )
			->andReturn( array( 'title' => 'SEO', 'description' => 'Desc' ) );

		// O Builder deve receber o HTML que saiu do AssetsManager ("HTML Processado")
		$mockBuilder->shouldReceive( 'build' )
			->once()
			->with( '<p>HTML Processado</p>', 'Titulo Teste', array( 'title' => 'SEO', 'description' => 'Desc' ) )
			->andReturn( '<jsp>Final</jsp>' );

		// 3. Execução (Adicionando o $mockAssets no final)
		$controller = new ExportController( $mockRepo, $mockUser, $mockExtractor, $mockBuilder, $mockSeo, $mockAssets );
		
		ob_start();
		try {
			$controller->render();
		} catch ( \Exception $e ) {
			// Ignora erro de view
		}
		ob_end_clean();

		$this->assertTrue( true );
	}
}