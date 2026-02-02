<?php
/**
 * Provedor de configuração via WordPress e Constantes.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */

namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\ConfigProviderInterface;

/**
 * Classe WPConfigProvider.
 *
 * Implementação concreta das configurações do plugin.
 * Centraliza o acesso a URLs do WordPress (`home_url`) e constantes globais
 * definidas no arquivo principal (`sultswriten.php`), como caminhos de exportação e domínios.
 *
 * @see ConfigProviderInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @author     Sults
 * @since      0.1.0
 */
class WPConfigProvider implements ConfigProviderInterface {

	/**
	 * {@inheritDoc}
	 */
	public function get_home_url(): string {
		return home_url();
	}

	/**
	 * {@inheritDoc}
	 *
	 * Retorna uma lista combinada de domínios padrão ('sults.com.br')
	 * e o domínio definido na constante `SULTSWRITEN_INTERNAL_DOMAIN`.
	 */
	public function get_internal_domains(): array {
		$domains = array(
			'sults.com.br',
			'artigo.sults.com.br',
		);

		if ( defined( 'SULTSWRITEN_INTERNAL_DOMAIN' ) ) {
			$domains[] = SULTSWRITEN_INTERNAL_DOMAIN;
		}

		return array_unique( array_filter( array_map( 'trim', $domains ) ) );
	}

	/**
	 * {@inheritDoc}
	 *
	 * Retorna o valor da constante `SULTSWRITEN_DOWNLOADS_PATH` ou um padrão.
	 */
	public function get_downloads_base_path(): string {
		return defined( 'SULTSWRITEN_DOWNLOADS_PATH' ) ? SULTSWRITEN_DOWNLOADS_PATH : '/downloads/';
	}

	/**
	 * {@inheritDoc}
	 *
	 * Retorna o valor da constante `SULTSWRITEN_TIPS_ICON` ou vazio.
	 */
	public function get_tips_icon_path(): string {
		return defined( 'SULTSWRITEN_TIPS_ICON' ) ? SULTSWRITEN_TIPS_ICON : '';
	}

	/**
	 * {@inheritDoc}
	 *
	 * Retorna o valor da constante `SULTSWRITEN_EXPORT_ZIP_PATH` ou o padrão para imagens.
	 */
	public function get_export_image_prefix(): string {
		return defined( 'SULTSWRITEN_EXPORT_ZIP_PATH' ) ? SULTSWRITEN_EXPORT_ZIP_PATH : 'sults/images/';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_default_jsp_folder(): string {
		return 'sults/pages/produtos';
	}
}