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

class WorkflowServiceProvider implements ServiceProviderInterface {

	public function register( Container $container ): void {

		// Policies.
		$container->set( WorkflowPolicy::class, fn() => new WorkflowPolicy() );
		$container->set(
			VisibilityPolicy::class,
			function ( $c ) {
				return new VisibilityPolicy( $c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ) );
			}
		);

		// Status Components.
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

		// Permission Components.
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

		// Notifications.
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

		// Media.
		$container->set( \Sults\Writen\Contracts\ImageProcessorInterface::class, fn() => new GDWebPProcessor() );
		$container->set( ThumbnailDisabler::class, fn() => new ThumbnailDisabler() );

		$container->set(
			MediaUploadManager::class,
			function ( $c ) {
				return new MediaUploadManager( $c->get( \Sults\Writen\Contracts\ImageProcessorInterface::class ) );
			}
		);

		// Manager Principal (Facade).
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

	public function boot( Container $container ): void {
		add_action( 'graphql_register_types', function() {
            
            register_graphql_object_type( 'WorkflowStatusConfig', [
                'description' => 'Configuração visual e lógica dos status',
                'fields' => [
                    'slug'      => [ 'type' => 'String' ],
                    'label'     => [ 'type' => 'String' ],
                    'bgStyle'   => [ 'type' => 'String' ],
                    'textStyle' => [ 'type' => 'String' ],
                ],
            ] );

            register_graphql_field( 'RootQuery', 'workflowStatuses', [
                'type'    => [ 'list_of' => 'WorkflowStatusConfig' ],
                'resolve' => function() {
                    $all_statuses = \Sults\Writen\Workflow\PostStatus\StatusConfig::get_all();
                    $visuals      = \Sults\Writen\Workflow\PostStatus\StatusVisuals::get_definitions();
                    $result       = [];

                    $result[] = [
                        'slug'      => 'draft',
                        'label'     => 'Rascunho',
                        'bgStyle'   => $visuals['draft']['bg'] ?? 'var(--color-neutral-500)',
                        'textStyle' => $visuals['draft']['text'] ?? 'var(--color-neutral-100)',
                    ];

                    foreach ( $all_statuses as $slug => $config ) {
                        $visual = isset($visuals[$slug]) ? $visuals[$slug] : ['bg' => '#ccc', 'text' => '#000'];
                        $result[] = [
                            'slug'      => $slug,
                            'label'     => $config['label'],
                            'bgStyle'   => $visual['bg'],
                            'textStyle' => $visual['text'],
                        ];
                    }
                    return $result;
                },
            ] );
        });

        $hook_manager = $container->get( HookManager::class );

        $services = array(
            $container->get( \Sults\Writen\Workflow\StatusManager::class ),
            $container->get( \Sults\Writen\Workflow\Media\MediaUploadManager::class ),
            $container->get( \Sults\Writen\Workflow\Media\ThumbnailDisabler::class ),
        );

        $hook_manager->register_services( $services );

		
    }
}
