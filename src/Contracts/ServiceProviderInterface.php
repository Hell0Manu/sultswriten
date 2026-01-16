<?php
namespace Sults\Writen\Contracts;

use Sults\Writen\Core\Container;

interface ServiceProviderInterface {
	/**
     * Registra as definições (classes) no Container.
     */
	public function register( Container $container ): void;

	/**
     * Inicializa os hooks e executa ações do provider.
     */
    public function boot( Container $container ): void;
}
