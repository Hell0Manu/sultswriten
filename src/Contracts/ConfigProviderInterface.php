<?php
/**
 * Interface de Configuração Global.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Contracts
 * @since      0.1.0
 */
namespace Sults\Writen\Contracts;

/**
 * Interface ConfigProviderInterface.
 *
 * Fornece acesso centralizado a constantes e configurações do ambiente,
 * como URLs base, caminhos de diretório e domínios permitidos.
 */
interface ConfigProviderInterface {
	/**
	 * Retorna a URL base do site.
	 *
	 * @since 0.1.0
	 * @return string Equivalente a home_url().
	 */
	public function get_home_url(): string;

	/**
	 * Retorna a lista de domínios internos considerados "locais".
	 * Usado para transformar links relativos em absolutos ou validar segurança.
	 *
	 * @since 0.1.0
	 * @return array<string> Ex: ['sults.com.br', 'localhost'].
	 */
	public function get_internal_domains(): array;

	/**
	 * Retorna o caminho base para downloads.
	 * Utilizado na transformação de blocos de arquivo.
	 *
	 * @since 0.1.0
	 * @return string Caminho relativo ou absoluto.
	 */
	public function get_downloads_base_path(): string;

	/**
	 * Retorna a URL do ícone usado nas dicas (tips).
	 *
	 * @since 0.1.0
	 * @return string URL da imagem.
	 */
	public function get_tips_icon_path(): string;

	/**
	 * Retorna o prefixo para imagens dentro do ZIP exportado.
	 *
	 * @since 0.1.0
	 * @return string Ex: 'sults/images/'.
	 */
    public function get_export_image_prefix(): string;

	/**
	 * Retorna a pasta padrão para arquivos JSP.
	 *
	 * @since 0.1.0
	 * @return string Ex: 'sults/pages/produtos'.
	 */
    public function get_default_jsp_folder(): string;
}
