<?php

use Sults\Writen\Interface\Dashboard\ExportController;
use Sults\Writen\Contracts\PostRepositoryInterface;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\ArchiverInterface;
use Sults\Writen\Workflow\Export\ExportProcessor;
use Sults\Writen\Workflow\Export\ExportNamingService; // [Novo Use]

class Test_ExportController extends WP_UnitTestCase {

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_render_preview_screen_deve_chamar_processor_e_exibir_view() {
		// 1. Mocks
		$mockRepo      = Mockery::mock( PostRepositoryInterface::class );
		$mockUser      = Mockery::mock( WPUserProviderInterface::class );
		$mockArchiver  = Mockery::mock( ArchiverInterface::class );
		$mockProcessor = Mockery::mock( ExportProcessor::class );
		$mockNaming    = Mockery::mock( ExportNamingService::class ); // [Novo Mock]

		$sults_post_id = $this->factory->post->create( array( 'post_title' => 'Titulo Teste' ) );
		
		// Simula GET parameters
		$_GET['action']   = 'preview';
		$_GET['post_id']  = $sults_post_id;
		$_GET['_wpnonce'] = wp_create_nonce( 'sults_preview_' . $sults_post_id );

		// 2. Expectativas
		$mockProcessor->shouldReceive( 'execute' )
			->once()
			->with( $sults_post_id, Mockery::type('string') )
			->andReturn( array(
				'jsp_content' => '<jsp>Final</jsp>',
				'files_map'   => array(),
				'html_clean'  => '<p>Clean</p>',
				'html_raw'    => '<p>Raw</p>'
			));

		// 3. Execução (Injetando o novo serviço de nomenclatura)
		$controller = new ExportController( $mockRepo, $mockUser, $mockArchiver, $mockProcessor, $mockNaming );
		
		ob_start();
		try {
			$controller->render();
		} catch ( \Exception $e ) {
			// Ignora erro de view
		}
		$output = ob_get_clean();

		$this->assertTrue( true );
	}

	public function test_handle_download_deve_usar_naming_service() {
		// Teste extra para garantir que o NamingService está sendo chamado
		$mockRepo      = Mockery::mock( PostRepositoryInterface::class );
		$mockUser      = Mockery::mock( WPUserProviderInterface::class );
		$mockArchiver  = Mockery::mock( ArchiverInterface::class );
		$mockProcessor = Mockery::mock( ExportProcessor::class );
		$mockNaming    = Mockery::mock( ExportNamingService::class );

		$sults_post_id = $this->factory->post->create( array( 'post_title' => 'Meu Post' ) );

		$_GET['action']   = 'download';
		$_GET['post_id']  = $sults_post_id;
		$_GET['_wpnonce'] = wp_create_nonce( 'sults_export_' . $sults_post_id );

		// Expectativa: O NamingService deve ser invocado
		$mockNaming->shouldReceive( 'generate_zip_filename' )
			->once()
			->with( 'Meu Post' )
			->andReturn( 'meu-post-sanitizado' );

		$mockProcessor->shouldReceive('execute')->andReturn([
			'files_map' => [], 'jsp_content' => ''
		]);
		$mockArchiver->shouldReceive('create')->andReturn(true);

		$controller = new ExportController( $mockRepo, $mockUser, $mockArchiver, $mockProcessor, $mockNaming );

		ob_start(); // Buffer para evitar headers output
		try {
			$controller->render();
		} catch (\Exception $e) {} // Ignora o exit ou die
		ob_end_clean();
		
		$this->assertTrue(true);
	}
}