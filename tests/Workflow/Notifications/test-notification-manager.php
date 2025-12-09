<?php

use Sults\Writen\Workflow\Notifications\NotificationManager;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;
use Sults\Writen\Contracts\NotificationRepositoryInterface;

class Test_Notification_Manager extends WP_UnitTestCase {

    protected function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }

    public function test_deve_criar_notificacao_quando_status_muda_e_autor_eh_diferente() {
        // 1. Mocks
        $mockUser       = Mockery::mock( WPUserProviderInterface::class );
        $mockStatus     = Mockery::mock( WPPostStatusProviderInterface::class );
        $mockRepository = Mockery::mock( NotificationRepositoryInterface::class ); // Novo Mock

        $author_id = 5;
        $editor_id = 10;

        $post = (object) array(
            'ID'          => 123,
            'post_title'  => 'Post Teste',
            'post_author' => $author_id,
            'post_type'   => 'post',
        );

        // 2. Expectativas
        $mockUser->shouldReceive( 'get_current_user_id' )->andReturn( $editor_id );

        $mockStatus->shouldReceive( 'get_status_object' )
            ->with( 'published' )
            ->andReturn( (object) array( 'label' => 'Publicado' ) );

        // AGORA VERIFICAMOS O REPOSITÓRIO, NÃO MAIS O USER META DIRETAMENTE
        $mockRepository->shouldReceive( 'add_notification' )
            ->once()
            ->with( 
                $author_id, 
                Mockery::on( function( $notification ) {
                    return isset($notification['msg']) && strpos( $notification['msg'], 'mudou para' ) !== false;
                })
            )
            ->andReturn( true );

        // 3. Instanciação com a nova dependência
        $manager = new NotificationManager( $mockUser, $mockStatus, $mockRepository );
        
        $manager->notify_author_on_status_change( 'published', 'draft', new WP_Post( $post ) );
        
        $this->assertTrue( true );
    }

    public function test_nao_deve_notificar_se_autor_for_o_mesmo_que_editou() {
        $mockUser       = Mockery::mock( WPUserProviderInterface::class );
        $mockStatus     = Mockery::mock( WPPostStatusProviderInterface::class );
        $mockRepository = Mockery::mock( NotificationRepositoryInterface::class );

        $author_id = 5;
        
        $mockUser->shouldReceive( 'get_current_user_id' )->andReturn( $author_id );
        
        // Garante que o repositório NUNCA é chamado
        $mockRepository->shouldReceive( 'add_notification' )->never();

        $post = (object) array( 'ID' => 1, 'post_author' => $author_id, 'post_type' => 'post' );

        $manager = new NotificationManager( $mockUser, $mockStatus, $mockRepository );
        $manager->notify_author_on_status_change( 'published', 'draft', new WP_Post( $post ) );

        $this->assertTrue( true );
    }
}