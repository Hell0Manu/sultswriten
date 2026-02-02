<?php
/**
 * Objeto de Transferência de Dados (DTO) para Exportação.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Export;

/**
 * Classe ExportPayload.
 *
 * Um container simples para transportar o resultado do processamento de assets.
 *
 * É retornado pelo `ExportAssetsManager` e consumido pelo `ExportProcessor`.
 * Contém o HTML com os caminhos das imagens já reescritos para referências locais
 * e o mapa de arquivos físicos que devem ser incluídos no arquivo ZIP.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export
 * @author     Sults
 * @since      0.1.0
 */
class ExportPayload {

	/**
	 * O conteúdo HTML processado.
	 *
	 * Neste HTML, as URLs de imagens originais (ex: `http://site.com/uploads/2023/foto.jpg`)
	 * já foram substituídas pelos caminhos relativos que serão usados dentro do ZIP/JSP
	 * (ex: `images/foto.jpg` ou `${zip_folder_prefix}/foto.jpg`).
	 *
	 * @var string
	 */
	public string $html_content;

	/**
	 * Mapa de arquivos a serem adicionados ao ZIP.
	 *
	 * Estrutura do Array:
	 * - Chave: Caminho absoluto do arquivo no servidor (Source).
	 * - Valor: Caminho relativo dentro do arquivo ZIP (Destination).
	 *
	 * Exemplo:
	 * [
	 * '/var/www/html/wp-content/uploads/2023/foto.jpg' => 'sults/pages/produtos/.../images/foto.jpg'
	 * ]
	 *
	 * @var array<string, string>
	 */
	public array $files_to_zip;

	/**
	 * Construtor.
	 *
	 * @param string $html_content HTML com caminhos reescritos.
	 * @param array  $files_to_zip Mapa de arquivos para compressão.
	 */
	public function __construct( string $html_content, array $files_to_zip ) {
		$this->html_content = $html_content;
		$this->files_to_zip = $files_to_zip;
	}
}