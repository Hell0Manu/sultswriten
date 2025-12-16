<?php
namespace Sults\Writen\Contracts;

interface JspHtmlSanitizerInterface {
	public function sanitize( string $html ): string;
}
