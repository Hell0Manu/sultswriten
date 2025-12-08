<?php

use Sults\Writen\Workflow\Notifications\NotificationManager;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;

class Test_Notification_Manager extends WP_UnitTestCase {

    protected function tearDown(): void {
        Mockery::close(); // Limpa os mocks após cada teste
        parent::tearDown();
    }

    /**
     * Cenário: Um Editor altera o status do post de um Redator.
     * Resultado Esperado: O sistema DEVE salvar uma notificação.
     */
    public function test_deve_criar_notificacao_quando_status_muda_e_autor_eh_diferente() {
        // 1. Criamos os atores falsos (Mocks)
        $mockUser   = Mockery::mock( WPUserProviderInterface::class );
        $mockStatus = Mockery::mock( WPPostStatusProviderInterface::class );

        // Definimos os IDs para o teste
        $author_id = 5;  // O dono do post (Redator)
        $editor_id = 10; // Quem está mexendo (Editor)

        // Criamos um post "fake" na memória
        $post = (object) array(
            'ID'          => 123,
            'post_title'  => 'Post Teste',
            'post_author' => $author_id,
            'post_type'   => 'post',
        );

        // 2. Ensinamos aos Mocks como se comportar
        
        // Quando perguntarem "quem está logado?", responda: "O Editor (ID 10)"
        $mockUser->shouldReceive( 'get_current_user_id' )->andReturn( $editor_id );
        
        // Quando pedirem o rótulo do status 'published', responda: "Publicado"
        $mockStatus->shouldReceive( 'get_status_object' )
            ->with( 'published' )
            ->andReturn( (object) array( 'label' => 'Publicado' ) );

        // Quando tentarem ler as notificações antigas do autor, devolva um array vazio (primeira notificação)
        $mockUser->shouldReceive( 'get_user_meta' )
            ->with( $author_id, '_sults_user_notifications', true )
            ->andReturn( array() );

        // A GRANDE PROVA: O método update_user_meta DEVE ser chamado 1 vez.
        // Validamos se ele está tentando salvar no usuário certo ($author_id)
        // E usamos uma função anônima para verificar se a mensagem gerada contém o texto esperado.
        $mockUser->shouldReceive( 'update_user_meta' )
            ->with( 
                $author_id, 
                '_sults_user_notifications', 
                Mockery::on( function( $notifs ) {
                    // Verifica se o array tem 1 item
                    // E se a mensagem contém "mudou para"
                    return count( $notifs ) === 1 && strpos( $notifs[0]['msg'], 'mudou para' ) !== false;
                }) 
            )
            ->once(); // Tem que acontecer exatamente uma vez

        // 3. Execução
        $manager = new NotificationManager( $mockUser, $mockStatus );
        
        // Simulamos a mudança de 'draft' para 'published'
        $manager->notify_author_on_status_change( 'published', 'draft', new WP_Post( $post ) );
        
        // 4. Confirmação
        $this->assertTrue( true ); // Se o Mockery não reclamar, o teste passou.
    }

    /**
     * Cenário: O próprio autor edita seu post.
     * Resultado Esperado: O sistema NÃO deve fazer nada (silêncio).
     */
    public function test_nao_deve_notificar_se_autor_for_o_mesmo_que_editou() {
        $mockUser   = Mockery::mock( WPUserProviderInterface::class );
        $mockStatus = Mockery::mock( WPPostStatusProviderInterface::class );

        $author_id = 5;
        
        // Quem está logado (get_current_user_id) é o mesmo ID do autor do post
        $mockUser->shouldReceive( 'get_current_user_id' )->andReturn( $author_id );
        
        // O método de salvar (update_user_meta) NUNCA deve ser chamado
        $mockUser->shouldReceive( 'update_user_meta' )->never();

        $post = (object) array( 'ID' => 1, 'post_author' => $author_id, 'post_type' => 'post' );

        $manager = new NotificationManager( $mockUser, $mockStatus );
        $manager->notify_author_on_status_change( 'published', 'draft', new WP_Post( $post ) );

        $this->assertTrue( true ); // Sucesso se nada acontecer.
    }
}