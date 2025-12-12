<?php
namespace Sults\Writen\Providers;

use Sults\Writen\Contracts\ServiceProviderInterface;
use Sults\Writen\Core\Container;

// Classes de Workflow e Status
use Sults\Writen\Workflow\StatusManager;
use Sults\Writen\Workflow\WorkflowPolicy;
use Sults\Writen\Workflow\PostStatus\PostStatusRegistrar;
use Sults\Writen\Workflow\PostStatus\AdminAssetsManager;
use Sults\Writen\Workflow\PostStatus\PostListPresenter;

// Permissões
use Sults\Writen\Workflow\Permissions\RoleManager;
use Sults\Writen\Workflow\Permissions\RoleLabelUpdater;
use Sults\Writen\Workflow\Permissions\MediaLibraryLimiter;
use Sults\Writen\Workflow\Permissions\PostListVisibility;
use Sults\Writen\Workflow\Permissions\DeletePrevention;
use Sults\Writen\Workflow\Permissions\PostEditingBlocker;
use Sults\Writen\Workflow\Permissions\PostRedirectionManager;
use Sults\Writen\Workflow\Permissions\VisibilityPolicy;

// Notificações e Mídia
use Sults\Writen\Workflow\Notifications\NotificationManager;
use Sults\Writen\Workflow\Media\MediaUploadManager;
use Sults\Writen\Workflow\Media\ThumbnailDisabler;
use Sults\Writen\Infrastructure\Media\GDWebPProcessor;

class WorkflowServiceProvider implements ServiceProviderInterface {

	public function register( Container $container ): void {
		
		// Policies
		$container->set( WorkflowPolicy::class, fn() => new WorkflowPolicy() );
		$container->set( VisibilityPolicy::class, function ( $c ) {
			return new VisibilityPolicy( $c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ) );
		});

		// Status Components
		$container->set( PostStatusRegistrar::class, function ( $c ) {
			return new PostStatusRegistrar( $c->get( \Sults\Writen\Contracts\WPPostStatusProviderInterface::class ) );
		});

		$container->set( AdminAssetsManager::class, function ( $c ) {
			return new AdminAssetsManager(
				$c->get( \Sults\Writen\Contracts\AssetLoaderInterface::class ),
				$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ),
				$c->get( \Sults\Writen\Infrastructure\AssetPathResolver::class )
			);
		});

		$container->set( PostListPresenter::class, function ( $c ) {
			return new PostListPresenter(
				$c->get( \Sults\Writen\Contracts\WPPostStatusProviderInterface::class ),
				$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class )
			);
		});

		// Permission Components
		$container->set( RoleLabelUpdater::class, fn() => new RoleLabelUpdater() );
		$container->set( DeletePrevention::class, fn() => new DeletePrevention() );
		
		$container->set( MediaLibraryLimiter::class, function ( $c ) {
			return new MediaLibraryLimiter( $c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ) );
		});

		$container->set( PostListVisibility::class, function ( $c ) {
			return new PostListVisibility( $c->get( VisibilityPolicy::class ) );
		});

		$container->set( RoleManager::class, function ( $c ) {
			return new RoleManager(
				$c->get( RoleLabelUpdater::class ),
				$c->get( MediaLibraryLimiter::class ),
				$c->get( PostListVisibility::class ),
				$c->get( DeletePrevention::class )
			);
		});

		$container->set( PostEditingBlocker::class, function ( $c ) {
			return new PostEditingBlocker(
				$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ),
				$c->get( \Sults\Writen\Contracts\WPPostStatusProviderInterface::class ),
				$c->get( \Sults\Writen\Contracts\RequestProviderInterface::class ),
				$c->get( WorkflowPolicy::class )
			);
		});

		$container->set( PostRedirectionManager::class, function ( $c ) {
			return new PostRedirectionManager(
				$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ),
				$c->get( \Sults\Writen\Contracts\WPPostStatusProviderInterface::class )
			);
		});

		// Notifications
		$container->set( NotificationManager::class, function ( $c ) {
			return new NotificationManager(
				$c->get( \Sults\Writen\Contracts\WPUserProviderInterface::class ),
				$c->get( \Sults\Writen\Contracts\WPPostStatusProviderInterface::class ),
				$c->get( \Sults\Writen\Contracts\NotificationRepositoryInterface::class )
			);
		});

		// Media
		$container->set( \Sults\Writen\Contracts\ImageProcessorInterface::class, fn() => new GDWebPProcessor() );
		$container->set( ThumbnailDisabler::class, fn() => new ThumbnailDisabler() );
		
		$container->set( MediaUploadManager::class, function ( $c ) {
			return new MediaUploadManager( $c->get( \Sults\Writen\Contracts\ImageProcessorInterface::class ) );
		});

		// Manager Principal (Facade)
		$container->set( StatusManager::class, function ( $c ) {
			return new StatusManager(
				$c->get( PostStatusRegistrar::class ),
				$c->get( AdminAssetsManager::class ),
				$c->get( PostListPresenter::class ),
				$c->get( PostEditingBlocker::class ),
				$c->get( RoleManager::class ),
				$c->get( NotificationManager::class ),
				$c->get( PostRedirectionManager::class )
			);
		});
	}
}