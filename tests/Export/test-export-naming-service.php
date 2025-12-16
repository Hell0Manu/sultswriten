<?php

use Sults\Writen\Workflow\Export\ExportNamingService;

class Test_ExportNamingService extends WP_UnitTestCase {

	public function test_deve_sanitizar_e_limitar_nome_arquivo() {
		$service = new ExportNamingService();

		$titulo_longo = 'Um título muito longo que definitivamente ultrapassa os cinquenta caracteres permitidos pelo sistema';
		
		$nome = $service->generate_zip_filename( $titulo_longo );

		$this->assertLessThanOrEqual( 50, strlen( $nome ) );
		$this->assertStringNotContainsString( ' ', $nome ); 
	}

	public function test_deve_usar_fallback_se_nome_vazio() {
		$service = new ExportNamingService();
		$this->assertEquals( 'exportacao-sults', $service->generate_zip_filename( '' ) );
	}
}