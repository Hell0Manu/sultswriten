<?php
/**
 * Resolvedor de caminhos para assets (CSS, JS, Imagens).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */

namespace Sults\Writen\Infrastructure;

/**
 * Classe AssetPathResolver.
 *
 * Responsável por gerar URLs absolutas para os arquivos estáticos do plugin.
 * Centraliza a lógica de diretórios (`src/assets/...`) para evitar strings
 * mágicas espalhadas pelo código.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @author     Sults
 * @since      0.1.0
 */
class AssetPathResolver {

	/**
	 * URL base do plugin (plugin_dir_url).
	 *
	 * @var string
	 */
	private string $base_url;

	/**
	 * Versão do plugin para controle de cache (cache busting).
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * Construtor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $base_url URL raiz do plugin.
	 * @param string $version  Versão atual do plugin.
	 */
	public function __construct( string $base_url, string $version ) {
		$this->base_url = $base_url;
		$this->version  = $version;
	}

	/**
	 * Retorna a URL completa para um arquivo CSS.
	 *
	 * @since 0.1.0
	 * @param string $filename Nome do arquivo (ex: 'style.css').
	 * @return string URL absoluta (ex: '.../src/assets/css/style.css').
	 */
	public function get_css_url( string $filename ): string {
		return $this->base_url . 'src/assets/css/' . $filename;
	}

	/**
	 * Retorna a URL completa para um arquivo JavaScript.
	 *
	 * @since 0.1.0
	 * @param string $filename Nome do arquivo (ex: 'app.js').
	 * @return string URL absoluta (ex: '.../src/assets/js/app.js').
	 */
	public function get_js_url( string $filename ): string {
		return $this->base_url . 'src/assets/js/' . $filename;
	}

	/**
	 * Retorna a URL completa para uma imagem.
	 *
	 * @since 0.1.0
	 * @param string $filename Nome do arquivo (ex: 'logo.png').
	 * @return string URL absoluta (ex: '.../src/assets/images/logo.png').
	 */
	public function get_image_url( string $filename ): string {
		return $this->base_url . 'src/assets/images/' . $filename;
	}

	/**
	 * Retorna a versão atual do plugin.
	 *
	 * Útil para passar como argumento de versão em `wp_enqueue_script`
	 * para forçar a atualização do cache do navegador quando o plugin muda.
	 *
	 * @since 0.1.0
	 * @return string A versão (ex: '1.0.0').
	 */
	public function get_version(): string {
		return $this->version;
	}
}