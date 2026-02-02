<?php
/**
 * Personalização do Tema de Login (White Label).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface\Theme
 * @since      0.1.0
 */

namespace Sults\Writen\Interface\Theme;

use Sults\Writen\Contracts\AssetLoaderInterface;
use Sults\Writen\Contracts\HookableInterface;
use Sults\Writen\Infrastructure\AssetPathResolver;

/**
 * Classe LoginTheme.
 *
 * Responsável por aplicar a identidade visual da Sults na tela de login do WordPress (wp-login.php).
 *
 * Funcionalidades:
 * 1. CSS Customizado: Carrega estilos para alterar cores, botões e input fields.
 * 2. Logotipo Personalizado: Substitui o logo do WordPress pelo logo da Sults via CSS inline dinâmico.
 * 3. UX: Ajusta o link e o título do logotipo para apontar para a home do site, não para wordpress.org.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Interface\Theme
 * @author     Sults
 * @since      0.1.0
 */
class LoginTheme implements HookableInterface {

	/** @var AssetLoaderInterface Carregador de assets. */
	private AssetLoaderInterface $asset_loader;

	/** @var AssetPathResolver Resolvedor de caminhos (para encontrar a imagem do logo). */
	private AssetPathResolver $asset_resolver;

	/**
	 * Construtor.
	 *
	 * @param AssetLoaderInterface $asset_loader   Serviço de carregamento.
	 * @param AssetPathResolver    $asset_resolver Serviço de resolução de caminhos.
	 */
	public function __construct(
		AssetLoaderInterface $asset_loader,
		AssetPathResolver $asset_resolver
	) {
		$this->asset_loader   = $asset_loader;
		$this->asset_resolver = $asset_resolver;
	}

	/**
	 * Registra os hooks de customização do login.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		// Enfileira estilos CSS na página de login.
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		
		// Altera a URL do link do logo.
		add_filter( 'login_headerurl', array( $this, 'change_logo_url' ) );
		
		// Altera o texto de hover do logo.
		add_filter( 'login_headertext', array( $this, 'change_logo_title' ) );
	}

	/**
	 * Carrega os estilos e injeta a imagem do logo.
	 *
	 * Utiliza `AssetPathResolver` para obter a URL correta da imagem `sults-logo.png`
	 * e gera um CSS inline para sobrescrever o background do h1 de login.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function enqueue_styles(): void {
		$version = $this->asset_resolver->get_version();

		// Variáveis CSS globais (cores da marca).
		$this->asset_loader->enqueue_style(
			'sultswriten-variables',
			$this->asset_resolver->get_css_url( 'variables.css' ),
			array(),
			$version
		);

		// CSS específico da página de login (override do WP).
		$this->asset_loader->enqueue_style(
			'sultswriten-login',
			$this->asset_resolver->get_css_url( 'login.css' ),
			array( 'sultswriten-variables', 'login' ), // Depende do estilo nativo 'login' para sobrescrevê-lo.
			$version
		);

		// Injeção dinâmica do logotipo.
		$logo_url   = $this->asset_resolver->get_image_url( 'sults-logo.png' );
		$custom_css = "
            #login h1 a, .login h1 a {
                background-image: url('{$logo_url}') !important;
                background-size: contain;
                width: 100%;
                max-width: 300px;
            }
        ";

		$this->asset_loader->add_inline_style( 'sultswriten-login', $custom_css );
	}

	/**
	 * Altera o link do logo para a Home do site (em vez de wordpress.org).
	 *
	 * @since 0.1.0
	 * @return string URL da home.
	 */
	public function change_logo_url(): string {
		return home_url();
	}

	/**
	 * Altera o título do logo para o nome do site (em vez de "Powered by WordPress").
	 *
	 * @since 0.1.0
	 * @return string Nome do site.
	 */
	public function change_logo_title(): string {
		return get_bloginfo( 'name' );
	}
}