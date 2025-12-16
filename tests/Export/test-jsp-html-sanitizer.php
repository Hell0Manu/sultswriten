<?php

use Sults\Writen\Workflow\Export\JspHtmlSanitizer;

class Test_JspHtmlSanitizer extends WP_UnitTestCase {

	public function test_deve_normalizar_atributos_para_aspas_simples() {
		$sanitizer = new JspHtmlSanitizer();

		$html_input = '<div class="container" id="main">Texto</div>';
		$esperado   = "<div class='container' id='main'>Texto</div>";

		$this->assertEquals( $esperado, $sanitizer->sanitize( $html_input ) );
	}

	public function test_deve_escapar_aspas_duplas_no_conteudo() {
		$sanitizer = new JspHtmlSanitizer();

		$html_input = '<p>Ele disse "Olá"</p>';
		$esperado   = '<p>Ele disse &#34;Olá&#34;</p>';

		$this->assertEquals( $esperado, $sanitizer->sanitize( $html_input ) );
	}
}