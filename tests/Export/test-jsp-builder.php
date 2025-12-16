<?php

use Sults\Writen\Workflow\Export\JspBuilder;

class Test_JspBuilder extends WP_UnitTestCase {

    public function test_deve_gerar_estrutura_jsp_correta() {
        $builder = new JspBuilder();

        $html_safe  = "<p class='text'>Conteúdo</p>"; 
        
        $title      = 'Titulo Pagina';
        $meta       = array( 'title' => 'SEO', 'description' => 'Desc' );

        $output = $builder->build( $html_safe, $title, $meta );

        $this->assertStringContainsString( '<!DOCTYPE html>', $output );
        
        $expected_html_param = "value=\"<p class='text'>Conteúdo</p>\"";
        $this->assertStringContainsString( $expected_html_param, $output );
    }
}