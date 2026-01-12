<?php

use Sults\Writen\Workflow\Export\HtmlExtractor;
use Sults\Writen\Workflow\Export\Transformers\ImageTransformer;
use Sults\Writen\Workflow\Export\Transformers\TableTransformer;
use Sults\Writen\Workflow\Export\Transformers\SultsTipTransformer;
use Sults\Writen\Workflow\Export\Transformers\BlockquoteTransformer;
use Sults\Writen\Workflow\Export\Transformers\FileBlockTransformer;
use Sults\Writen\Workflow\Export\Transformers\LinkTransformer;
use Sults\Writen\Contracts\AttachmentProviderInterface;
use Sults\Writen\Contracts\ConfigProviderInterface;
use Sults\Writen\Workflow\Export\ExportConfig; // Importante para o teste saber as classes permitidas

class Test_HtmlExtractor extends WP_UnitTestCase {

    private HtmlExtractor $extractor;
    private $mockAttachmentProvider;
    private $mockConfigProvider;

    public function setUp(): void {
        parent::setUp();

        $this->mockConfigProvider = Mockery::mock( ConfigProviderInterface::class );
        $this->mockConfigProvider->shouldReceive('get_home_url')->andReturn('http://example.org');
        $this->mockConfigProvider->shouldReceive('get_internal_domains')->andReturn('sults.com.br');
        $this->mockConfigProvider->shouldReceive('get_downloads_base_path')->andReturn('/sults/downloads/artigos/checklist/');
        $this->mockConfigProvider->shouldReceive('get_tips_icon_path')->andReturn('/images/tip.png');

        $this->mockAttachmentProvider = Mockery::mock( AttachmentProviderInterface::class );
        $this->mockAttachmentProvider->shouldReceive('get_attachment_id_by_url')->andReturn(0);
        $this->mockAttachmentProvider->shouldReceive('get_image_src')->andReturn(null);
        $this->mockAttachmentProvider->shouldReceive('get_attachment_url')->andReturn(null);

        $transformers = array(
            new ImageTransformer( $this->mockAttachmentProvider, $this->mockConfigProvider ),
            new LinkTransformer( $this->mockConfigProvider ),
            new TableTransformer(),
            new SultsTipTransformer( $this->mockConfigProvider ),
            new BlockquoteTransformer(),
            new FileBlockTransformer( $this->mockAttachmentProvider, $this->mockConfigProvider ),
        );

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

        // CORREÇÃO: Aspas duplas, pois é saída padrão do DOMDocument
        $this->assertStringContainsString( 'class="dica-sults"', $result );
        $this->assertStringContainsString( '<h3>Dica SULTS</h3>', $result );
        $this->assertStringContainsString( '<p>Esta é uma dica importante.</p>', $result );
    }

    public function test_deve_envolver_tabelas_em_div_responsiva() {
        $content = '<table><tr><td>Dados</td></tr></table>';
        $post_id = $this->factory->post->create( array( 'post_content' => $content ) );
        
        $result = $this->extractor->extract( get_post( $post_id ) );

        // CORREÇÃO: Aspas duplas
        $this->assertStringContainsString( 'class="table-content"', $result );
        $this->assertStringContainsString( '<table>', $result );
    }

    public function test_deve_adicionar_target_blank_em_links_externos() {
        $content = '<a href="https://google.com">Google</a>';
        $post_id = $this->factory->post->create( array( 'post_content' => $content ) );

        $result = $this->extractor->extract( get_post( $post_id ) );

        // CORREÇÃO: Aspas duplas
        $this->assertStringContainsString( 'target="_blank"', $result );
        $this->assertStringContainsString( 'rel="noopener noreferrer"', $result );
    }

    public function test_nao_deve_alterar_links_internos() {
        $content = "<a href='https://sults.com.br/contato'>Contato</a>";
        $post_id = $this->factory->post->create( array( 'post_content' => $content ) );

        $result = $this->extractor->extract( get_post( $post_id ) );

        $this->assertStringNotContainsString( "target='_blank'", $result );
    }
    
    public function test_deve_manter_classes_permitidas() {
        // CORREÇÃO: Este teste substitui o antigo "substituir aspas", 
        // pois a conversão de aspas agora é no JspBuilder.
        // Aqui testamos se o Extractor mantém as classes corretas (agora via DOM).
        
        $content = "<p class='aligncenter'>Texto</p>";
        $post_id = $this->factory->post->create( array( 'post_content' => $content ) );

        $result = $this->extractor->extract( get_post( $post_id ) );

        // Espera aspas duplas, padrão do HTML
        $this->assertStringContainsString( 'class="aligncenter"', $result );
    }
}