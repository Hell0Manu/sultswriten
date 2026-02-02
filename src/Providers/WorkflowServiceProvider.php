<?php
namespace Sults\Writen\Providers;

use Sults\Writen\Contracts\ServiceProviderInterface;
use Sults\Writen\Core\Container;
use Sults\Writen\Core\HookManager;

// Classes de Workflow e Status.
use Sults\Writen\Workflow\StatusManager;
use Sults\Writen\Workflow\WorkflowPolicy;
use Sults\Writen\Workflow\PostStatus\PostStatusRegistrar;
use Sults\Writen\Workflow\PostStatus\AdminAssetsManager;
use Sults\Writen\Workflow\PostStatus\PostListPresenter;

// Permissões.
use Sults\Writen\Workflow\Permissions\RoleManager;
use Sults\Writen\Workflow\Permissions\RoleLabelUpdater;
use Sults\Writen\Workflow\Permissions\MediaLibraryLimiter;
use Sults\Writen\Workflow\Permissions\PostListVisibility;
use Sults\Writen\Workflow\Permissions\DeletePrevention;
use Sults\Writen\Workflow\Permissions\PostEditingBlocker;
use Sults\Writen\Workflow\Permissions\PostRedirectionManager;
use Sults\Writen\Workflow\Permissions\VisibilityPolicy;

// Notificações e Mídia.
use Sults\Writen\Workflow\Notifications\NotificationManager;
use Sults\Writen\Workflow\Media\MediaUploadManager;
use Sults\Writen\Workflow\Media\ThumbnailDisabler;
use Sults\Writen\Infrastructure\Media\GDWebPProcessor;
use Sults\Writen\Infrastructure\WPMailer;
use Sults\Writen\Contracts\MailerInterface;

/**
 * Classe WorkflowServiceProvider.
 *
 * Responsável por registrar e inicializar todos os serviços relacionados à
 * lógica de negócio do fluxo editorial, incluindo:
 * - Registro de Status Personalizados (Post Status).
 * - Gestão de Permissões e Bloqueios de Edição.
 * - Controle de Visibilidade de Posts e Mídia.
 * - Disparo de Notificações de E-mail.
 * - Manipulação e Otimização de Uploads.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Providers
 * @author     Sults
 * @since      0.1.0
 */
class WorkflowServiceProvider implements ServiceProviderInterface {

	/**
	 * Registra os serviços de workflow no container.
	 *
	 * Define as dependências para:
	 * 1. Políticas (Policies): Regras de validação reutilizáveis.
	 * 2. Componentes de Status: Registro e apresentação visual dos status.
	 * 3. Permissões: Controle fino do que cada role (Redator, Editor) pode fazer.
	 * 4. Notificações: Envio de e-mails transacionais.
	 * 5. Mídia: Otimização para WebP e restrições da biblioteca.
	 *
	 * @since 0.1.0
	 *
	 * @param Container $container O container de DI.
	 * @return void
	 */
	public function register( Container $container ): void {

		// --- Policies (Regras de Negócio) ---
		$container->set( WorkflowPolicy::class, fn() => new WorkflowPolicy() );
		$container->set(
			VisibilityPolicy::class,
			function ( $c ) {
				return new VisibilityPolicy( $c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ) );
			}
		);

		// --- Componentes de Status ---
		$container->set(
			PostStatusRegistrar::class,
			function ( $c ) {
				return new PostStatusRegistrar( $c->get( \Sults\Writen\Contracts\WPPostStatusProviderInterface::class ) );
			}
		);

		$container->set(
			AdminAssetsManager::class,
			function ( $c ) {
				return new AdminAssetsManager(
					$c->get( \Sults\Writen\Contracts\AssetLoaderInterface::class ),
					$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ),
					$c->get( \Sults\Writen\Infrastructure\AssetPathResolver::class )
				);
			}
		);

		$container->set(
			PostListPresenter::class,
			function ( $c ) {
				return new PostListPresenter(
					$c->get( \Sults\Writen\Contracts\WPPostStatusProviderInterface::class ),
					$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class )
				);
			}
		);

		// --- Componentes de Permissão e Segurança ---
		$container->set( RoleLabelUpdater::class, fn() => new RoleLabelUpdater() );
		$container->set( DeletePrevention::class, fn() => new DeletePrevention() );

		$container->set(
			MediaLibraryLimiter::class,
			function ( $c ) {
				return new MediaLibraryLimiter( $c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ) );
			}
		);

		$container->set(
			PostListVisibility::class,
			function ( $c ) {
				return new PostListVisibility( $c->get( VisibilityPolicy::class ) );
			}
		);

		$container->set(
			RoleManager::class,
			function ( $c ) {
				return new RoleManager(
					$c->get( RoleLabelUpdater::class ),
					$c->get( MediaLibraryLimiter::class ),
					$c->get( PostListVisibility::class ),
					$c->get( DeletePrevention::class )
				);
			}
		);

		$container->set(
			PostEditingBlocker::class,
			function ( $c ) {
				return new PostEditingBlocker(
					$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ),
					$c->get( \Sults\Writen\Contracts\WPPostStatusProviderInterface::class ),
					$c->get( \Sults\Writen\Contracts\RequestProviderInterface::class ),
					$c->get( WorkflowPolicy::class )
				);
			}
		);

		$container->set(
			PostRedirectionManager::class,
			function ( $c ) {
				return new PostRedirectionManager(
					$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ),
					$c->get( \Sults\Writen\Contracts\WPPostStatusProviderInterface::class )
				);
			}
		);

		// --- Notificações ---
		$container->set(
			MailerInterface::class,
			function ( $c ) {
				return new WPMailer(
					$c->get( \Sults\Writen\Infrastructure\AssetPathResolver::class ) 
				);
			}
		);

		$container->set(
			NotificationManager::class,
			function ( $c ) {
				return new NotificationManager(
					$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ),
					$c->get( \Sults\Writen\Contracts\WPPostStatusProviderInterface::class ),
					$c->get( \Sults\Writen\Contracts\NotificationRepositoryInterface::class ),
					$c->get( MailerInterface::class )
				);
			}
		);

		// --- Mídia e Uploads ---
		$container->set( \Sults\Writen\Contracts\ImageProcessorInterface::class, fn() => new GDWebPProcessor() );
		$container->set( ThumbnailDisabler::class, fn() => new ThumbnailDisabler() );

		$container->set(
			MediaUploadManager::class,
			function ( $c ) {
				return new MediaUploadManager( $c->get( \Sults\Writen\Contracts\ImageProcessorInterface::class ) );
			}
		);

		// --- Fachada Principal (Workflow Manager) ---
		$container->set(
			StatusManager::class,
			function ( $c ) {
				return new StatusManager(
					$c->get( PostStatusRegistrar::class ),
					$c->get( AdminAssetsManager::class ),
					$c->get( PostListPresenter::class ),
					$c->get( PostEditingBlocker::class ),
					$c->get( RoleManager::class ),
					$c->get( NotificationManager::class ),
					$c->get( PostRedirectionManager::class )
				);
			}
		);
	}

	/**
	 * Inicializa os serviços de workflow registrando seus hooks.
	 *
	 * @since 0.1.0
	 *
	 * @param Container $container O container de DI.
	 * @return void
	 */
	public function boot( Container $container ): void {
        $hook_manager = $container->get( HookManager::class );

        $services = array(
            $container->get( \Sults\Writen\Workflow\StatusManager::class ),
            $container->get( \Sults\Writen\Workflow\Media\MediaUploadManager::class ),
            $container->get( \Sults\Writen\Workflow\Media\ThumbnailDisabler::class ),
        );

        $hook_manager->register_services( $services );
    }
}
