<?php
namespace Sults\Writen\Workflow\Export;

class ExportPayload {
	/**
	 * O HTML final com os caminhos das imagens atualizados (ex: src="images/img_1.jpg").
	 */
	public string $html_content;

	/**
	 * Lista de arquivos para o ZIP.
	 * Formato: ['caminho/local/no/servidor.jpg' => 'caminho/dentro/do/zip/img_1.jpg']
	 */
	public array $files_to_zip;

	public function __construct( string $html_content, array $files_to_zip ) {
		$this->html_content = $html_content;
		$this->files_to_zip = $files_to_zip;
	}
}
