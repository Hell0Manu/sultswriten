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
}
