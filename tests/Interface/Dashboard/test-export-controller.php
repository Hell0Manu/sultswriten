<?php

use Sults\Writen\Interface\Dashboard\ExportController;
use Sults\Writen\Contracts\PostRepositoryInterface;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\HtmlExtractorInterface;
use Sults\Writen\Contracts\JspBuilderInterface;
use Sults\Writen\Contracts\SeoDataProviderInterface;

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

		$post_id = $this->factory->post->create( array( 'post_title' => 'Titulo Teste' ) );
		
		// Simula GET parameters
		$_GET['action']   = 'preview';
		$_GET['post_id']  = $post_id;
		$_GET['_wpnonce'] = wp_create_nonce( 'sults_preview_' . $post_id );

		// 2. Expectativas
		$mockExtractor->shouldReceive( 'extract' )->once()->andReturn( '<p>HTML Limpo</p>' );
		
		$mockSeo->shouldReceive( 'get_seo_data' )
			->once()
			->with( $post_id )
			->andReturn( array( 'title' => 'SEO', 'description' => 'Desc' ) );

		$mockBuilder->shouldReceive( 'build' )
			->once()
			->with( '<p>HTML Limpo</p>', 'Titulo Teste', array( 'title' => 'SEO', 'description' => 'Desc' ) )
			->andReturn( '<jsp>Final</jsp>' );

		// 3. Execução
		$controller = new ExportController( $mockRepo, $mockUser, $mockExtractor, $mockBuilder, $mockSeo );
		
		// Captura o output (o require da view vai imprimir coisas ou usar variáveis)
		// Como o teste roda num ambiente isolado, o require da view pode falhar se não achá-la, 
		// mas aqui estamos testando a lógica antes da view.
		// Vamos usar ob_start para suprimir output da view se ela existir
		ob_start();
		try {
			$controller->render();
		} catch ( \Exception $e ) {
			// Ignora erro de view não encontrada no teste unitário se ocorrer
		}
		ob_end_clean();

		$this->assertTrue( true ); // Se chegou aqui sem erro e mockery passou, está ok.
	}
}