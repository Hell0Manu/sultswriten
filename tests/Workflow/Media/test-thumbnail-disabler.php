<?php

use Sults\Writen\Workflow\Media\ThumbnailDisabler;

class Test_ThumbnailDisabler extends WP_UnitTestCase {

    public function test_register_adiciona_filtros_de_bloqueio() {
        $disabler = new ThumbnailDisabler();
        $disabler->register();

        // 1. Verifica se o filtro de tamanhos intermediários foi adicionado
        // O callback '__return_empty_array' é uma string no WP
        $this->assertEquals( 
            10, 
            has_filter( 'intermediate_image_sizes_advanced', '__return_empty_array' ),
            'Deve registrar filtro para retornar array vazio de tamanhos.'
        );

        // 2. Verifica se o filtro de limite de imagem grande foi adicionado
        $this->assertEquals( 
            10, 
            has_filter( 'big_image_size_threshold', '__return_false' ),
            'Deve registrar filtro para desativar threshold de imagem grande.'
        );
    }

    /**
     * Teste de integração: Verifica se os filtros realmente alteram os valores.
     */
    public function test_filtros_retornam_valores_esperados() {
        $disabler = new ThumbnailDisabler();
        $disabler->register();

        // Simula a execução do filtro 'intermediate_image_sizes_advanced'
        // Passando um array cheio, deve sair vazio.
        $sizes = array( 'medium' => array(), 'large' => array() );
        $filtered_sizes = apply_filters( 'intermediate_image_sizes_advanced', $sizes );
        $this->assertEmpty( $filtered_sizes );

        // Simula a execução do filtro 'big_image_size_threshold'
        // Passando um valor padrão (ex: 2560), deve retornar false.
        $threshold = apply_filters( 'big_image_size_threshold', 2560 );
        $this->assertFalse( $threshold );
    }
}