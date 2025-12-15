<?php
namespace Sults\Writen\Workflow\Export;

class ExportConfig {
	/**
	 * Lista de classes CSS permitidas na exportação.
	 * Qualquer classe não listada aqui será removida dos elementos HTML.
	 */
	public const ALLOWED_CLASSES = array(
		'wp-block-columns',
		'wp-block-column',
		'wp-block-image',
		'wp-block-table',
		'wp-block-separator',
		'wp-block-quote',
		'aligncenter',
		'alignleft',
		'alignright',
		'is-style-default',
		'has-text-align-center',
		'dica-sults',
	);
}
