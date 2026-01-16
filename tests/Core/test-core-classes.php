<?php
/**
 * Testes para Activator, Deactivator e Plugin.
 *
 * @package Sults\Writen
 */

use Sults\Writen\Core\Plugin;
use Sults\Writen\Core\Activator;
use Sults\Writen\Core\Deactivator;
use Sults\Writen\Core\Container;
use Sults\Writen\Workflow\Permissions\RoleCapabilityManager;
use Sults\Writen\Infrastructure\RewriteManager;

class Test_Core_Classes extends WP_UnitTestCase {

    /**
     * Teste para verificar se o Plugin registra os serviços no Container.
     */
    public function test_plugin_deve_registrar_servicos_no_container() {
        $sults_plugin = new Plugin();
        
        // Usa Reflection para acessar a propriedade privada 'container'
        $reflection = new ReflectionClass( $sults_plugin );
        $container_prop = $reflection->getProperty( 'container' );
        $container_prop->setAccessible( true );
        $container = $container_prop->getValue( $sults_plugin );

        $this->assertInstanceOf( Container::class, $container );

        // Verifica se alguns serviços chave foram registrados e são instanciáveis
        $this->assertInstanceOf( 
            \Sults\Writen\Contracts\WPUserProviderInterface::class, 
            $container->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ) 
        );

        $this->assertInstanceOf( 
            \Sults\Writen\Workflow\StatusManager::class, 
            $container->get( \Sults\Writen\Workflow\StatusManager::class ) 
        );
        
        $this->assertInstanceOf(
            \Sults\Writen\Workflow\Permissions\MediaLibraryLimiter::class,
            $container->get( \Sults\Writen\Workflow\Permissions\MediaLibraryLimiter::class )
        );
    }

    /**
     * Teste de Integração para o Activator.
     * Verifica se as capacidades são aplicadas ao rodar o activate.
     */
    public function test_activator_deve_aplicar_regras() {
        // Mock de overload para verificar se as classes internas são chamadas
        // Nota: Overload no Mockery pode ser instável em alguns ambientes. 
        // Aqui faremos um teste de efeito (state verification).
        
        Activator::activate();

        // Verifica se uma capability do 'contributor' foi adicionada (definida no RoleCapabilityManager)
        $role = get_role( 'contributor' );
        $this->assertTrue( $role->has_cap( 'upload_files' ), 'Activator deve adicionar upload_files ao contributor.' );
    }

    /**
     * Teste de Integração para o Deactivator.
     */
    public function test_deactivator_deve_reverter_regras() {
        // Primeiro garantimos que está ativado
        Activator::activate();
        
        // Executa desativação
        Deactivator::deactivate();

        // Verifica se a capability foi removida
        $role = get_role( 'contributor' );
        $this->assertFalse( $role->has_cap( 'upload_files' ), 'Deactivator deve remover upload_files do contributor.' );
    }
}