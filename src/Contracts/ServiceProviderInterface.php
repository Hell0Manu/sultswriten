<?php
namespace Sults\Writen\Contracts;

use Sults\Writen\Core\Container;

interface ServiceProviderInterface {
	public function register( Container $container ): void;
}
