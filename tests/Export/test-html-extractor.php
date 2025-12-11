<?php

use Sults\Writen\Workflow\Export\HtmlExtractor;
// Importa os Transformers
use Sults\Writen\Workflow\Export\Transformers\ImageTransformer;
use Sults\Writen\Workflow\Export\Transformers\TableTransformer;
use Sults\Writen\Workflow\Export\Transformers\SultsTipTransformer;
use Sults\Writen\Workflow\Export\Transformers\BlockquoteTransformer;
use Sults\Writen\Workflow\Export\Transformers\FileBlockTransformer;
use Sults\Writen\Workflow\Export\Transformers\LinkTransformer;
// Importa Interfaces para Mock
use Sults\Writen\Contracts\AttachmentProviderInterface;
use Sults\Writen\Contracts\ConfigProviderInterface;

class Test_HtmlExtractor extends WP_UnitTestCase {

    private HtmlExtractor $extractor;
    private $mockAttachmentProvider;
    private $mockConfigProvider;

    public function setUp(): void {
        parent::setUp();

        // 1. Mock do ConfigProvider (Simula as configurações)
        $this->mockConfigProvider = Mockery::mock( ConfigProviderInterface::class );
        $this->mockConfigProvider->shouldReceive('get_home_url')->andReturn('http://example.org');
        $this->mockConfigProvider->shouldReceive('get_internal_domain')->andReturn('sults.com.br');
        $this->mockConfigProvider->shouldReceive('get_downloads_base_path')->andReturn('/sults/downloads/artigos/checklist/');

        // 2. Mock do AttachmentProvider (Simula o WP Media)
        $this->mockAttachmentProvider = Mockery::mock( AttachmentProviderInterface::class );
        // Define retornos padrão "seguros" para não quebrar testes que não usam imagens
        $this->mockAttachmentProvider->shouldReceive('get_attachment_id_by_url')->andReturn(0);
        $this->mockAttachmentProvider->shouldReceive('get_image_src')->andReturn(null);
        $this->mockAttachmentProvider->shouldReceive('get_attachment_url')->andReturn(null);

        // 3. Instancia os Transformers REAIS injetando os MOCKS
        // Isso configura nosso pipeline completo de teste
        $transformers = array(
            new ImageTransformer( $this->mockAttachmentProvider, $this->mockConfigProvider ),
            new LinkTransformer( $this->mockConfigProvider ),
            new TableTransformer(),
            new SultsTipTransformer(),
            new BlockquoteTransformer(),
            new FileBlockTransformer( $this->mockAttachmentProvider, $this->mockConfigProvider ),
        );

        // 4. Instancia o Extractor com o Pipeline montado
        $this->extractor = new HtmlExtractor( $transformers, $this->mockConfigProvider );
    }

    protected function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }

    public function test_deve_limpar_elementos_basicos() {
        $post_id = $this->factory->post->create( array(
            'post_content' => '<p id="remove-me">Texto Limpo</p>'
        ) );
        $post = get_post( $post_id );

        $result = $this->extractor->extract( $post );

        $this->assertStringNotContainsString( 'id="remove-me"', $result );
        $this->assertStringContainsString( '<p>Texto Limpo</p>', $result );
    }

    public function test_deve_transformar_pre_em_dica_sults() {
        $content = '<pre>Esta é uma dica importante.</pre>';
        $post_id = $this->factory->post->create( array( 'post_content' => $content ) );
        $post = get_post( $post_id );

        $result = $this->extractor->extract( $post );

        // Como usamos DOMDocument, as aspas nos atributos podem variar, mas normalizamos para simples no final do extract
        $this->assertStringContainsString( "class='dica-sults'", $result );
        $this->assertStringContainsString( '<h3>Dica Sults</h3>', $result );
        $this->assertStringContainsString( '<p>Esta é uma dica importante.</p>', $result );
    }

    public function test_deve_envolver_tabelas_em_div_responsiva() {
        $content = '<table><tr><td>Dados</td></tr></table>';
        $post_id = $this->factory->post->create( array( 'post_content' => $content ) );
        
        $result = $this->extractor->extract( get_post( $post_id ) );

        $this->assertStringContainsString( "class='table-content'", $result );
        $this->assertStringContainsString( '<table>', $result );
    }

    public function test_deve_adicionar_target_blank_em_links_externos() {
        // http://google.com é externo (configuramos interno como sults.com.br no mock)
        $content = '<a href="https://google.com">Google</a>';
        $post_id = $this->factory->post->create( array( 'post_content' => $content ) );

        $result = $this->extractor->extract( get_post( $post_id ) );

        $this->assertStringContainsString( "target='_blank'", $result );
        $this->assertStringContainsString( "rel='noopener noreferrer'", $result );
    }

    public function test_nao_deve_alterar_links_internos() {
        // Link interno simulado
        $content = '<a href="https://sults.com.br/contato">Contato</a>';
        $post_id = $this->factory->post->create( array( 'post_content' => $content ) );

        $result = $this->extractor->extract( get_post( $post_id ) );

        $this->assertStringNotContainsString( "target='_blank'", $result );
    }
    
    public function test_deve_substituir_aspas_duplas_por_simples_no_html_final() {
        $content = '<p class="aligncenter">Texto</p>';
        $post_id = $this->factory->post->create( array( 'post_content' => $content ) );

        $result = $this->extractor->extract( get_post( $post_id ) );

        // Verifica se manteve a aspa simples
        $this->assertStringContainsString( "class='aligncenter'", $result );
    }
}