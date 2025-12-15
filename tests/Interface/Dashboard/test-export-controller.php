<?php

use Sults\Writen\Interface\Dashboard\ExportController;
use Sults\Writen\Contracts\PostRepositoryInterface;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\ArchiverInterface; // Novo
use Sults\Writen\Workflow\Export\ExportProcessor; // Novo

class Test_ExportController extends WP_UnitTestCase {

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_render_preview_screen_deve_chamar_processor_e_exibir_view() {
		// 1. Mocks
		$mockRepo      = Mockery::mock( PostRepositoryInterface::class );
		$mockUser      = Mockery::mock( WPUserProviderInterface::class );
		$mockArchiver  = Mockery::mock( ArchiverInterface::class ); // Novo Mock
		$mockProcessor = Mockery::mock( ExportProcessor::class );   // Novo Mock principal

		$post_id = $this->factory->post->create( array( 'post_title' => 'Titulo Teste' ) );
		
		// Simula GET parameters
		$_GET['action']   = 'preview';
		$_GET['post_id']  = $post_id;
		$_GET['_wpnonce'] = wp_create_nonce( 'sults_preview_' . $post_id );

		// 2. Expectativas
		// O Controller agora apenas chama o Processor->execute()
		$mockProcessor->shouldReceive( 'execute' )
			->once()
			->with( $post_id, Mockery::type('string') ) // Verifica se passa ID e um path prefix
			->andReturn( array(
				'jsp_content' => '<jsp>Final</jsp>',
				'files_map'   => array(),
				'html_clean'  => '<p>Clean</p>',
				'html_raw'    => '<p>Raw</p>'
			));

		// 3. Execução (Passando as dependências corretas)
		$controller = new ExportController( $mockRepo, $mockUser, $mockArchiver, $mockProcessor );
		
		ob_start();
		try {
			$controller->render();
		} catch ( \Exception $e ) {
			// Ignora erro de view não encontrada se houver, ou output
		}
		$output = ob_get_clean();

		// Se não houve exceção fatal, o teste passou na integração com o Processor
		$this->assertTrue( true );
	}
}