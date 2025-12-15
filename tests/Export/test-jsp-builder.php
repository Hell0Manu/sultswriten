<?php

use Sults\Writen\Workflow\Export\JspBuilder;

class Test_JspBuilder extends WP_UnitTestCase {

    public function test_deve_gerar_estrutura_jsp_correta() {
        $builder = new JspBuilder();

        // Input com aspas duplas
        $html       = '<p class="text">Conteúdo</p>'; 
        
        $title      = 'Como fazer checklist';
        $meta       = array(
            'title'       => 'SEO Título',
            'description' => 'SEO Descrição',
        );

        $output = $builder->build( $html, $title, $meta );

        $this->assertStringContainsString( '<!DOCTYPE html>', $output );
        $this->assertStringContainsString( '<jsp:include page="/sults/components/default/include_meta.jsp">', $output );
        $this->assertStringContainsString( 'value="SEO Título"', $output );

        // VERIFICAÇÃO AJUSTADA:
        // Como mantivemos o Regex, o output esperado converte class="text" para class='text'
        $expected_html_param = "value=\"<p class='text'>Conteúdo</p>\"";
        
        $this->assertStringContainsString( $expected_html_param, $output );
    }

    public function test_deve_escapar_caracteres_especiais_no_titulo() {
        $builder = new JspBuilder();

        $html  = '';
        $title = 'Título com "aspas" & <tags>';
        $meta  = array(); 

        $output = $builder->build( $html, $title, $meta );

        // Para o Título, usamos htmlspecialchars, então continua escapando para &quot;
        // Isso está correto pois não passa pelo Regex de atributos HTML
        $esperado = 'value="Título com &quot;aspas&quot; &amp; &lt;tags&gt;"';

        $this->assertStringContainsString( $esperado, $output );
    }

    public function test_deve_usar_fallback_se_metadados_estiverem_ausentes() {
        $builder = new JspBuilder();

        $html = '<p>Conteúdo</p>';
        $titulo_pagina = 'Título Original';
        $meta_vazio = array(); 

        $output = $builder->build( $html, $titulo_pagina, $meta_vazio );

        $this->assertStringContainsString( 
            'name="meta_title" value="Título Original"', 
            $output 
        );

        $this->assertStringContainsString( 
            'name="meta_description" value=""', 
            $output 
        );
    }
}