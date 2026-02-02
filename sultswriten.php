<?php
/**
 * O arquivo de inicialização do plugin.
 *
 * Este arquivo é responsável por inicializar o plugin, definir constantes globais
 * e carregar as dependências via Composer.
 *
 * @link              https://www.sults.com.br/
 * @since             0.1.0
 * @package           Sults\Writen
 *
 * @wordpress-plugin
 * Plugin Name:       Sults Writen
 * Plugin URI:        https://www.sults.com.br/
 * Description:       Plugin de exportação e fluxo editorial SULTS.
 * Version:           0.1.1
 * Author:            Sults
 * Text Domain:       sultswriten
 * Domain Path:       /languages
 */
use Sults\Writen\Core\Plugin;
use Sults\Writen\Core\Activator;
use Sults\Writen\Core\Deactivator;
use Sults\Writen\Core\Uninstaller;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Caminho absoluto para o arquivo principal do plugin.
 */
define( 'SULTSWRITEN_FILE', __FILE__ );

/**
 * Caminho do diretório do plugin no sistema de arquivos.
 */
define( 'SULTSWRITEN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * URL base do diretório do plugin.
 */
define( 'SULTSWRITEN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Versão atual do plugin.
 */
define( 'SULTSWRITEN_VERSION', '0.1.0' );

/**
 * Domínio interno utilizado para validações e links.
 */
if ( ! defined( 'SULTSWRITEN_INTERNAL_DOMAIN' ) ) {
	define( 'SULTSWRITEN_INTERNAL_DOMAIN', 'sults.com.br' );
}

/**
 * Caminho relativo para a pasta de downloads de checklists.
 */
if ( ! defined( 'SULTSWRITEN_DOWNLOADS_PATH' ) ) {
	define( 'SULTSWRITEN_DOWNLOADS_PATH', '/sults/downloads/artigos/checklist/' );
}

/**
 * Caminho relativo para o ícone padrão de dicas (tips).
 */
if ( ! defined( 'SULTSWRITEN_TIPS_ICON' ) ) {
	define( 'SULTSWRITEN_TIPS_ICON', '/sults/images/icones/produtos/dica-sults.webp' );
}

/**
 * Caminho relativo para exportação de arquivos ZIP.
 */
if ( ! defined( 'SULTSWRITEN_EXPORT_ZIP_PATH' ) ) {
	define( 'SULTSWRITEN_EXPORT_ZIP_PATH', 'sults/images/ilustracoes/secundaria/produtos/checklist/artigos/' );
}

// Carrega o autoloader do Composer.
if ( file_exists( SULTSWRITEN_PATH . 'vendor/autoload.php' ) ) {
	require SULTSWRITEN_PATH . 'vendor/autoload.php';
}

/**
 * Inicia a execução do plugin.
 *
 * Instancia a classe principal `Plugin` e chama o método `run`.
 * Caso a classe não seja encontrada (erro no composer ou arquivo faltando),
 * exibe uma mensagem de erro fatal no painel administrativo.
 *
 * @since 0.1.0
 * @return void
 */
function sultswriten_run() {
	if ( class_exists( Plugin::class ) ) {
		( new Plugin() )->run();
	} elseif ( is_admin() ) {
			wp_die( 'ERRO FATAL SULTS: A classe Plugin não foi encontrada. Verifique se executou "composer install".' );
	}
}

/**
 * O código que roda durante a ativação do plugin.
 * @see Sults\Writen\Core\Activator
 */
register_activation_hook( SULTSWRITEN_FILE, array( Activator::class, 'activate' ) );

/**
 * O código que roda durante a desativação do plugin.
 * @see Sults\Writen\Core\Deactivator
 */
register_deactivation_hook( SULTSWRITEN_FILE, array( Deactivator::class, 'deactivate' ) );

/**
 * O código que roda durante a desinstalação do plugin.
 * @see Sults\Writen\Core\Uninstaller
 */
register_uninstall_hook( SULTSWRITEN_FILE, array( Uninstaller::class, 'uninstall' ) );

// Inicia o plugin.
sultswriten_run();
