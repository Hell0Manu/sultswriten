<?php

use Sults\Writen\Workflow\Export\ExportAssetsManager;

class Test_ExportAssetsManager extends WP_UnitTestCase {

	private string $temp_file;
	private string $upload_dir;
	private string $upload_url;

	protected function setUp(): void {
		parent::setUp();
		
		$dirs = wp_upload_dir();
		$this->upload_dir = $dirs['basedir'];
		$this->upload_url = $dirs['baseurl'];

		// Cria uma imagem fake
		$this->temp_file = $this->upload_dir . '/teste-sults.jpg';
		file_put_contents( $this->temp_file, 'fake image content' );
	}

	protected function tearDown(): void {
		if ( file_exists( $this->temp_file ) ) {
			unlink( $this->temp_file );
		}
		parent::tearDown();
	}

	public function test_deve_renomear_imagem_baseado_no_title_com_caminho_customizado() {
		$manager = new ExportAssetsManager();
		$img_url = $this->upload_url . '/teste-sults.jpg';
		
		$html_input = '<img src="' . $img_url . '" title="Capa do Vídeo Checklist" alt="Alt Ignorado">';
		$sults_path_prefix = 'sults/images/produtos/checklist'; // Caminho solicitado

		$sults_payload = $manager->process( $html_input, $sults_path_prefix );

		$expected_path = 'sults/images/produtos/checklist/capa_do_video_checklist.jpg';
		
		$this->assertArrayHasKey( $this->temp_file, $sults_payload->files_to_zip );
		$this->assertEquals( $expected_path, $sults_payload->files_to_zip[ $this->temp_file ] );
		$this->assertStringContainsString( 'src="/' . $expected_path . '"', $sults_payload->html_content );
	}

	public function test_deve_tratar_nomes_duplicados() {
		$manager = new ExportAssetsManager();
		$img_url = $this->upload_url . '/teste-sults.jpg';

		// Envolvemos em uma DIV para o DOMDocument processar múltiplos elementos corretamente
		$html_input  = '<div>';
		$html_input .= '<img src="' . $img_url . '" title="Ícone">';
		$html_input .= '<img src="' . $img_url . '" title="Ícone">';
		$html_input .= '</div>';

		// CORREÇÃO: Passando o segundo argumento obrigatório 'img/'
		$sults_payload = $manager->process( $html_input, 'img/' );

		// Verifica se gerou sufixos diferentes
		$this->assertStringContainsString( 'icone.jpg', $sults_payload->html_content );
		$this->assertStringContainsString( 'icone_1.jpg', $sults_payload->html_content );
	}

	public function test_deve_ignorar_imagens_externas() {
		$manager = new ExportAssetsManager();
		$external_url = 'https://google.com/logo.png';
		$html_input   = '<img src="' . $external_url . '">';

		// CORREÇÃO: Passando argumento 'assets/' (mesmo que não vá usar)
		$sults_payload = $manager->process( $html_input, 'assets/' );

		$this->assertEmpty( $sults_payload->files_to_zip );
		$this->assertStringContainsString( $external_url, $sults_payload->html_content );
	}

	public function test_deve_lidar_com_html_vazio() {
		$manager = new ExportAssetsManager();
		
		// CORREÇÃO: Passando argumento 'assets/'
		$sults_payload = $manager->process( '', 'assets/' );

		$this->assertEmpty( $sults_payload->html_content );
		$this->assertEmpty( $sults_payload->files_to_zip );
	}
}