<?php
namespace Sults\Writen\Contracts;

interface ConfigProviderInterface {
	/**
	 * Retorna a URL base do site (equivalente a home_url()).
	 */
	public function get_home_url(): string;

	/**
	 * Retorna o domínio interno principal para verificação de links.
	 * Ex: 'sults.com.br'
	 */
	public function get_internal_domains(): array;

	/**
	 * Retorna o caminho base para downloads (usado no FileBlockTransformer).
	 */
	public function get_downloads_base_path(): string;

	/**
	 * Retorna a URL/Caminho do ícone usado nas dicas.
	 */
	public function get_tips_icon_path(): string;

	/**
     * Retorna o prefixo/caminho para as imagens dentro do ZIP exportado.
     * Padrão: 'sults/images/'
     */
    public function get_export_image_prefix(): string;

    /**
     * Retorna a pasta padrão para arquivos JSP caso não definida no post.
     * Padrão: 'sults/pages/produtos'
     */
    public function get_default_jsp_folder(): string;
}
