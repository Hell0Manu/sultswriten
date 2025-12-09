<?php

use Sults\Writen\Workflow\Media\MediaUploadManager;
use Sults\Writen\Contracts\ImageProcessorInterface;

class Test_MediaUploadManager extends WP_UnitTestCase {

    protected function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Verifica se o filtro do WordPress é registrado corretamente.
     */
    public function test_register_adiciona_filtro_wp_handle_upload() {
        // Mock da dependência (não precisamos da implementação real aqui)
        $mockProcessor = Mockery::mock( ImageProcessorInterface::class );
        
        $manager = new MediaUploadManager( $mockProcessor );
        $manager->register();

        // Verifica se o hook foi registrado com a prioridade padrão (10)
        $this->assertEquals( 
            10, 
            has_filter( 'wp_handle_upload', array( $manager, 'handle_upload_conversion' ) ) 
        );
    }

    /**
     * Verifica se o Manager delega o processamento para a classe ImageProcessor.
     */
    public function test_handle_upload_delega_para_o_processador() {
        $mockProcessor = Mockery::mock( ImageProcessorInterface::class );
        
        $uploadData = array(
            'file' => '/tmp/imagem-teste.jpg',
            'url'  => 'http://site.local/wp-content/uploads/imagem-teste.jpg',
            'type' => 'image/jpeg'
        );

        $processedData = array(
            'file' => '/tmp/imagem-teste.webp', // Simula mudança
            'url'  => 'http://site.local/wp-content/uploads/imagem-teste.webp',
            'type' => 'image/webp'
        );

        // Expectativa: O método process deve ser chamado 1 vez com os dados de upload
        $mockProcessor->shouldReceive( 'process' )
            ->once()
            ->with( $uploadData )
            ->andReturn( $processedData );

        $manager = new MediaUploadManager( $mockProcessor );
        
        // Executa o método
        $result = $manager->handle_upload_conversion( $uploadData );

        // Verifica o retorno
        $this->assertEquals( $processedData, $result );
    }
}