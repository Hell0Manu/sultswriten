<?php
namespace Sults\Writen\Contracts;

interface RequestProviderInterface {
	public function is_post_method(): bool;
}
