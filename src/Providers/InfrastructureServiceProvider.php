<?php
/**
 * Provedor de Serviços de Infraestrutura.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Providers
 * @since      0.1.0
 */

namespace Sults\Writen\Providers;

use Sults\Writen\Contracts\ServiceProviderInterface;
use Sults\Writen\Core\Container;
use Sults\Writen\Core\HookManager;
use Sults\Writen\Infrastructure\WPUserProvider;
use Sults\Writen\Infrastructure\WPAssetLoader;
use Sults\Writen\Infrastructure\WPPostStatusProvider;
use Sults\Writen\Infrastructure\WPNotificationRepository;
use Sults\Writen\Infrastructure\WPPostRepository;
use Sults\Writen\Infrastructure\WPAttachmentProvider;
use Sults\Writen\Infrastructure\WPConfigProvider;
use Sults\Writen\Infrastructure\RequestBlocker;
use Sults\Writen\Infrastructure\AssetPathResolver;
use Sults\Writen\Infrastructure\FeatureDisabler;
use Sults\Writen\Infrastructure\PostConfigurator;
use Sults\Writen\Infrastructure\HomeRedirector;
use Sults\Writen\Infrastructure\NotFoundRedirector;
use Sults\Writen\Infrastructure\WPFileSystem;

/**
 * Classe InfrastructureServiceProvider.
 *
 * Responsável por registrar no Container de Injeção de Dependência (DI)
 * todas as implementações concretas da camada de Infraestrutura.
 *
 * É aqui que definimos: "Quando alguém pedir FileSystemInterface, entregue WPFileSystem".
 * Isso desacopla a lógica de negócio das funções globais do WordPress.
 *
 * @see ServiceProviderInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Providers
 * @author     Sults
 * @since      0.1.0
 */
class InfrastructureServiceProvider implements ServiceProviderInterface {

	/**
	 * Registra os serviços no container.
	 *
	 * Vincula Interfaces -> Implementações Concretas.
	 *
	 * @since 0.1.0
	 *
	 * @param Container $container A instância do container DI.
	 * @return void
	 */
	public function register( Container $container ): void {

		// 1. Gerenciador de Hooks (Core).
		$container->set( HookManager::class, fn() => new HookManager() );

		// 2. Wrappers do WordPress (Adapters para isolar funções globais).
		$container->set( \Sults\Writen\Contracts\WPUserProviderInterface::class, fn() => new WPUserProvider() );
		$container->set( \Sults\Writen\Contracts\AssetLoaderInterface::class, fn() => new WPAssetLoader() );
		$container->set( \Sults\Writen\Contracts\WPPostStatusProviderInterface::class, fn() => new WPPostStatusProvider() );
		$container->set( \Sults\Writen\Contracts\NotificationRepositoryInterface::class, fn() => new WPNotificationRepository() );
		$container->set( \Sults\Writen\Contracts\AttachmentProviderInterface::class, fn() => new WPAttachmentProvider() );

		// Filesystem (precisa ser instanciado via closure para garantir lazy loading).
		$container->set(
			\Sults\Writen\Contracts\FileSystemInterface::class,
			function () {
				return new WPFileSystem();
			}
		);

		// 3. Configuração e Segurança.
		$container->set( \Sults\Writen\Contracts\ConfigProviderInterface::class, fn() => new WPConfigProvider() );
		$container->set( \Sults\Writen\Contracts\RequestProviderInterface::class, fn() => new RequestBlocker() );

		// 4. Redirecionamentos de Rota.
		$container->set( HomeRedirector::class, fn() => new HomeRedirector() );
		$container->set( NotFoundRedirector::class, fn() => new NotFoundRedirector() );

		// 5. Repositórios de Dados (Data Access Layer).
		$container->set(
			\Sults\Writen\Contracts\PostRepositoryInterface::class,
			function ( $c ) {
				// Injeta a política de visibilidade no repositório.
				return new WPPostRepository(
					$c->get( \Sults\Writen\Workflow\Permissions\VisibilityPolicy::class )
				);
			}
		);

		// 6. Utilitários e Helpers de Infraestrutura.
		$container->set( FeatureDisabler::class, fn() => new FeatureDisabler() );

		$container->set(
			AssetPathResolver::class,
			function () {
				// Injeta constantes globais definidas no arquivo principal do plugin.
				return new AssetPathResolver( SULTSWRITEN_URL, SULTSWRITEN_VERSION );
			}
		);

		// 7. Manipulação de Arquivos (ZipArchiver).
		$container->set(
			\Sults\Writen\Contracts\ArchiverInterface::class,
			function () {
				return new \Sults\Writen\Infrastructure\ZipArchiver();
			}
		);

		$container->set(
			PostConfigurator::class,
			fn() => new PostConfigurator()
		);
	}

	/**
	 * Inicializa serviços que precisam rodar imediatamente.
	 *
	 * Registra os hooks para serviços que modificam o comportamento global do WP
	 * assim que o plugin carrega (ex: desativar emojis, redirecionar home).
	 *
	 * @since 0.1.0
	 *
	 * @param Container $container A instância do container DI.
	 * @return void
	 */
	public function boot( Container $container ): void {
		/** @var HookManager $hook_manager */
		$hook_manager = $container->get( HookManager::class );

		// Lista de serviços que devem ser "ligados" (registrar seus hooks) no boot.
		$services = array(
			$container->get( \Sults\Writen\Infrastructure\FeatureDisabler::class ),
			$container->get( \Sults\Writen\Infrastructure\PostConfigurator::class ),
			$container->get( \Sults\Writen\Infrastructure\HomeRedirector::class ),
			$container->get( \Sults\Writen\Infrastructure\NotFoundRedirector::class ),
		);

		$hook_manager->register_services( $services );
	}
}
