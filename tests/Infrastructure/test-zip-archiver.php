<?php

use Sults\Writen\Infrastructure\ZipArchiver;

class Test_ZipArchiver extends WP_UnitTestCase {

	private string $zip_path;
	private string $dummy_file;

	protected function setUp(): void {
		parent::setUp();
		$this->zip_path   = tempnam( sys_get_temp_dir(), 'test_zip' ) . '.zip';
		$this->dummy_file = tempnam( sys_get_temp_dir(), 'dummy' );
		file_put_contents( $this->dummy_file, 'teste imagem' );
	}

	protected function tearDown(): void {
		if ( file_exists( $this->zip_path ) ) unlink( $this->zip_path );
		if ( file_exists( $this->dummy_file ) ) unlink( $this->dummy_file );
		parent::tearDown();
	}

	public function test_deve_criar_arquivo_zip_com_conteudo() {
		$archiver = new ZipArchiver();

		// Mapa de arquivos físicos (simulando imagem)
		$files = array(
			$this->dummy_file => 'pasta/imagem.jpg'
		);

		// Mapa de strings (simulando JSP)
		$strings = array(
			'index.jsp' => '<h1>Ola Mundo</h1>'
		);

		$sucesso = $archiver->create( $this->zip_path, $files, $strings );

		$this->assertTrue( $sucesso, 'Falha ao criar o arquivo ZIP.' );
		$this->assertFileExists( $this->zip_path );

		// Verificação extra: Abrir o zip criado e checar se os arquivos estão lá
		$zip = new ZipArchive();
		if ( $zip->open( $this->zip_path ) === true ) {
			// Verifica se index.jsp está lá
			$this->assertNotFalse( $zip->getFromName( 'index.jsp' ) );
			// Verifica se a imagem está lá com o nome novo
			$this->assertNotFalse( $zip->getFromName( 'pasta/imagem.jpg' ) );
			$zip->close();
		}
	}
}