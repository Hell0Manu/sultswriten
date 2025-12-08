<?php

use Sults\Writen\Workflow\StatusManager;
use Sults\Writen\Workflow\PostStatus\PostStatusRegistrar;
use Sults\Writen\Workflow\PostStatus\AdminAssetsManager;
use Sults\Writen\Workflow\PostStatus\PostListPresenter;
use Sults\Writen\Workflow\Permissions\PostEditingBlocker;
use Sults\Writen\Workflow\Permissions\RoleManager;
use Sults\Writen\Infrastructure\RewriteManager;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;

class Test_Workflow_Manager extends WP_UnitTestCase {

    protected function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }

    public function test_status_registrar_chama_provider_corretamente() {
        $mockProvider = Mockery::mock( WPPostStatusProviderInterface::class );
        
        // Espera que o método 'register' seja chamado 4 vezes (para os 4 status customizados)
        $mockProvider->shouldReceive( 'register' )
            ->times( 4 )
            ->with( Mockery::type('string'), Mockery::type('array') )
            ->andReturn( (object)[] );

        $registrar = new PostStatusRegistrar( $mockProvider );
        $registrar->register();

        $this->assertTrue( true );
    }

    public function test_status_manager_inicia_componentes() {
        // 1. Criar Mocks (sem expectativas de execução imediata)
        // Como add_action apenas agendará, não usamos shouldReceive('register')->once() aqui,
        // pois isso obrigaria a execução imediata, que não ocorre.
        
        $mockRegistrar = Mockery::mock( PostStatusRegistrar::class );
        $mockAssets    = Mockery::mock( AdminAssetsManager::class );
        $mockPresenter = Mockery::mock( PostListPresenter::class );
        $mockBlocker   = Mockery::mock( PostEditingBlocker::class );
        $mockRoleMgr   = Mockery::mock( RoleManager::class );

        // 2. Preparar ambiente
        // Simula ser admin para garantir que os hooks de admin sejam registrados
        set_current_screen( 'dashboard' ); 

        // 3. Executar o método da classe testada
        $manager = new StatusManager( $mockRegistrar, $mockAssets, $mockPresenter, $mockBlocker, $mockRoleMgr );
        $manager->register();

        // 4. Asserções: Verificar se os hooks foram REGISTRADOS no WordPress
        // A prioridade padrão no código é 5 para registrar e 10 para blocker/role
        
        $this->assertEquals( 
            5, 
            has_action( 'init', array( $mockRegistrar, 'register' ) ),
            'O registrar deve ser hookado no init com prioridade 5' 
        );

        $this->assertEquals( 
            10, 
            has_action( 'init', array( $mockBlocker, 'register' ) ),
            'O blocker deve ser hookado no init com prioridade 10'
        );

        $this->assertEquals( 
            10, 
            has_action( 'init', array( $mockRoleMgr, 'register' ) ),
            'O role manager deve ser hookado no init com prioridade 10'
        );

        // Verifica Assets e Presenter (que estão dentro do bloco is_admin())
        $this->assertEquals( 
            10, 
            has_action( 'init', array( $mockAssets, 'register' ) ),
            'Assets Manager deve ser registrado no init'
        );

        $this->assertEquals( 
            10, 
            has_action( 'init', array( $mockPresenter, 'register' ) ),
            'Post List Presenter deve ser registrado no init'
        );
    }

    public function test_rewrite_manager_faz_flush() {
        $manager = new RewriteManager();
        $manager->flush();
        $this->assertTrue( true );
    }
}