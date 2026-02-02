<?php

namespace Sults\Writen\Providers;

use Sults\Writen\Contracts\ServiceProviderInterface;
use Sults\Writen\Core\Container;
use Sults\Writen\Core\HookManager;
use Sults\Writen\Structure\StructureManager;
use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Contracts\WPPostStatusProviderInterface;
use Sults\Writen\Interface\CategoryColorManager;
use Sults\Writen\Workflow\WorkflowPolicy;
use Sults\Writen\Contracts\PostRepositoryInterface;

/**
 * Classe StructureServiceProvider.
 *
 * Responsável por registrar e inicializar os serviços relacionados à estrutura
 * visual e organizacional do plugin, gerenciando como os posts são exibidos,
 * hierarquias e identificadores visuais (cores de categoria).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Providers
 * @author     Sults
 * @since      0.1.0
 */
class StructureServiceProvider implements ServiceProviderInterface {

	/**
	 * Registra o gerenciador de estrutura no Container.
	 *
	 * O `StructureManager` é uma classe complexa que depende de vários outros serviços
	 * (UI, Dados de Usuário, Status e Repositórios). Aqui fazemos a injeção de todas
	 * essas dependências para garantir que ele funcione corretamente.
	 *
	 * @since 0.1.0
	 *
	 * @param Container $container O container de DI.
	 * @return void
	 */
	public function register( Container $container ): void {
		$container->set(
			StructureManager::class,
			function ( Container $c ) {
				return new StructureManager(
					$c->get( WPUserProviderInterface::class ),
					$c->get( AssetLoaderInterface::class ),
					$c->get( WPPostStatusProviderInterface::class ),
					// Injeta o gerenciador de cores (UI).
					$c->get( CategoryColorManager::class ),
					// Injeta regras de negócio (Policies).
					$c->get( WorkflowPolicy::class ),
					// Injeta acesso a dados (Repository).
					$c->get( PostRepositoryInterface::class )
				);
			}
		);
	}

	/**
	 * Inicializa o StructureManager registrando seus hooks.
	 *
	 * @since 0.1.0
	 *
	 * @param Container $container O container de DI.
	 * @return void
	 */
	public function boot( Container $container ): void {
		/** @var HookManager $hook_manager */
        $hook_manager = $container->get( HookManager::class );

        $services = array(
            $container->get( \Sults\Writen\Structure\StructureManager::class ),
        );

        $hook_manager->register_services( $services );
    }
}
