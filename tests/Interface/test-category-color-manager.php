<?php

use Sults\Writen\Interface\CategoryColorManager;
use Sults\Writen\Contracts\AssetLoaderInterface;

class Test_CategoryColorManager extends WP_UnitTestCase {

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
		// Limpa o POST global após cada teste
		$_POST = array();
	}

	public function test_deve_salvar_meta_se_nonce_for_valido() {
		$mockLoader = Mockery::mock( AssetLoaderInterface::class );
		$manager    = new CategoryColorManager( $mockLoader );

		// 1. Cria uma categoria de teste
		$term_id = $this->factory->term->create( array( 'taxonomy' => 'category' ) );

		// 2. Simula o POST com Nonce Válido
		$_POST['sults_category_color']       = '#ff0000';
		$_POST['sults_category_color_nonce'] = wp_create_nonce( 'sults_save_category_color' );

		// 3. Executa o salvamento
		$manager->save_meta( $term_id );

		// 4. Verifica se salvou no banco
		$saved_color = get_term_meta( $term_id, '_sults_category_color', true );
		$this->assertEquals( '#ff0000', $saved_color, 'A cor deveria ser salva com nonce válido.' );
	}

	public function test_nao_deve_salvar_se_nonce_estiver_ausente() {
		$mockLoader = Mockery::mock( AssetLoaderInterface::class );
		$manager    = new CategoryColorManager( $mockLoader );

		$term_id = $this->factory->term->create( array( 'taxonomy' => 'category' ) );

		// Simula POST SEM o nonce
		$_POST['sults_category_color'] = '#00ff00';

		$manager->save_meta( $term_id );

		$saved_color = get_term_meta( $term_id, '_sults_category_color', true );
		$this->assertEmpty( $saved_color, 'Não deve salvar meta sem nonce.' );
	}

	public function test_nao_deve_salvar_se_nonce_for_invalido() {
		$mockLoader = Mockery::mock( AssetLoaderInterface::class );
		$manager    = new CategoryColorManager( $mockLoader );

		$term_id = $this->factory->term->create( array( 'taxonomy' => 'category' ) );

		// Simula POST com nonce ERRADO
		$_POST['sults_category_color']       = '#0000ff';
		$_POST['sults_category_color_nonce'] = 'nonce_invalido_hacker';

		$manager->save_meta( $term_id );

		$saved_color = get_term_meta( $term_id, '_sults_category_color', true );
		$this->assertEmpty( $saved_color, 'Não deve salvar meta com nonce inválido.' );
	}

	public function test_deve_sanitizar_a_cor_antes_de_salvar() {
		$mockLoader = Mockery::mock( AssetLoaderInterface::class );
		$manager    = new CategoryColorManager( $mockLoader );

		$term_id = $this->factory->term->create( array( 'taxonomy' => 'category' ) );

		// Simula POST com código malicioso ou cor inválida
		$_POST['sults_category_color']       = '<script>alert(1)</script>'; // Inválido para hex
		$_POST['sults_category_color_nonce'] = wp_create_nonce( 'sults_save_category_color' );

		$manager->save_meta( $term_id );

		$saved_color = get_term_meta( $term_id, '_sults_category_color', true );
		
		// sanitize_hex_color retorna null/vazio se não for hexadecimal válido
		$this->assertEmpty( $saved_color, 'Deve ignorar valores que não sejam cores hexadecimais.' );
	}
}