<?php

use Sults\Writen\Workflow\Export\JspBuilder;

class Test_JspBuilder extends WP_UnitTestCase {

	public function test_deve_gerar_estrutura_jsp_correta() {
		$builder = new JspBuilder();

		$html       = '<p>Conteúdo do Artigo</p>';
		$title      = 'Como fazer checklist';
		$meta       = array(
			'title'       => 'SEO Título',
			'description' => 'SEO Descrição',
		);

		$output = $builder->build( $html, $title, $meta );

		// 1. Verifica se a estrutura básica do JSP está presente
		$this->assertStringContainsString( '<!DOCTYPE html>', $output );
		$this->assertStringContainsString( '<jsp:include page="/sults/components/default/include_meta.jsp">', $output );

		// 2. Verifica se os metadados foram injetados corretamente
		$this->assertStringContainsString( 'value="SEO Título"', $output );
		$this->assertStringContainsString( 'value="SEO Descrição"', $output );

		// 3. Verifica se o conteúdo HTML foi injetado no parâmetro description1
		// Nota: O HTML está dentro de aspas simples no template: value='{$html_content}'
        $this->assertStringContainsString( 'value="<p class=\'text\'>Conteúdo</p>"', $output );
	}

    public function test_deve_escapar_caracteres_especiais_no_titulo() {
            $builder = new \Sults\Writen\Workflow\Export\JspBuilder();

            $html  = '';
            $title = 'Título com "aspas" & <tags>';
            $meta  = array(); // Teste sem meta dados para verificar fallback

            $output = $builder->build( $html, $title, $meta );

            // CORREÇÃO: Esperamos 'Título' (UTF-8) e não 'T&iacute;tulo'
            // Mas as aspas e tags DEVEM ser convertidas.
            $esperado = 'value="Título com &quot;aspas&quot; &amp; &lt;tags&gt;"';

            $this->assertStringContainsString( $esperado, $output );
            
            // Verifica também o fallback do meta title
            $this->assertStringContainsString( 'name="meta_title" ' . $esperado, $output );
    }

    /**
 * Teste para verificar o comportamento de Fallback.
 * Arquivo: tests/Export/test-jsp-builder.php
 */
public function test_deve_usar_fallback_se_metadados_estiverem_ausentes() {
    $builder = new \Sults\Writen\Workflow\Export\JspBuilder();

    $html = '<p>Conteúdo</p>';
    $titulo_pagina = 'Título Original da Página';
    
    // Simula que o SEO não retornou dados (array vazio ou chaves faltando)
    $meta_vazio = array(); 

    $output = $builder->build( $html, $titulo_pagina, $meta_vazio );

    // 1. Verifica se o "meta_title" assumiu o valor do "$titulo_pagina" (Fallback)
    // Esperamos: <jsp:param name="meta_title" value="Título Original da Página"/>
    $this->assertStringContainsString( 
        'name="meta_title" value="Título Original da Página"', 
        $output,
        'Deveria usar o título da página quando o meta title está ausente.'
    );

    // 2. Verifica se a "meta_description" ficou vazia (Fallback)
    // Esperamos: <jsp:param name="meta_description" value=""/>
    $this->assertStringContainsString( 
        'name="meta_description" value=""', 
        $output,
        'Deveria gerar value="" quando a meta description está ausente.'
    );
}
}