<?php

use Sults\Writen\Workflow\PostStatus\PostStatusRegistrar;


class Test_Integration_Status extends WP_UnitTestCase {

	public function test_status_foram_registrados_no_wordpress() {
		$status_obj = get_post_status_object( 'suspended' );

		$this->assertNotNull( $status_obj, 'O status "suspended" não foi registrado no WP.' );
		$this->assertEquals( 'Suspenso', $status_obj->label );

		$this->assertNotNull( get_post_status_object( 'finished' ), 'O status "finalizado" não existe.' );
	}

	public function test_post_pode_ser_salvo_com_status_customizado() {
		$post_id = $this->factory->post->create(
			array(
				'post_title'  => 'Artigo de Teste',
				'post_status' => 'suspended',
			)
		);

		$post = get_post( $post_id );

		$this->assertEquals( 'suspended', $post->post_status );
	}
}