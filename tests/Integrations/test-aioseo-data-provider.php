<?php

use Sults\Writen\Integrations\AIOSEO\AioseoDataProvider;

class Test_Aioseo_Data_Provider extends WP_UnitTestCase {

	public function test_deve_retornar_titulo_padrao_se_aioseo_nao_existir() {
		// Cria um post fake no banco de dados de teste
		$post_id = $this->factory->post->create( array(
			'post_title' => 'Meu Título Original',
		) );

		// Instancia o provider
		$provider = new AioseoDataProvider();

		// Executa
		$data = $provider->get_seo_data( $post_id );

		// Verifica
		// Como o ambiente de teste não tem o AIOSEO ativo, ele deve cair no fallback
		$this->assertEquals( 'Meu Título Original', $data['title'] );
		$this->assertEquals( '', $data['description'] );
	}

	public function test_deve_retornar_array_com_chaves_corretas() {
		$post_id = $this->factory->post->create();
		$provider = new AioseoDataProvider();
		$data = $provider->get_seo_data( $post_id );

		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'description', $data );
	}
}