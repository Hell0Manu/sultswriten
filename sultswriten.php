<?php
/**
 * Plugin Name:  Sults Writen
 * Plugin URI:   https://www.sults.com.br/
 * Description:  Plugin de exportação e fluxo editorial SULTS.
 * Version:      0.1.1
 * Author:       Sults
 * Text Domain:  sultswriten
 * Domain Path:  /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'SULTSWRITEN_FILE', __FILE__ );
define( 'SULTSWRITEN_PATH', plugin_dir_path( __FILE__ ) );
define( 'SULTSWRITEN_URL', plugin_dir_url( __FILE__ ) );
define( 'SULTSWRITEN_VERSION', '0.1.0' );

if ( ! defined( 'SULTSWRITEN_INTERNAL_DOMAIN' ) ) {
	define( 'SULTSWRITEN_INTERNAL_DOMAIN', 'sults.com.br' );
}

if ( ! defined( 'SULTSWRITEN_DOWNLOADS_PATH' ) ) {
	define( 'SULTSWRITEN_DOWNLOADS_PATH', '/sults/downloads/artigos/checklist/' );
}

if ( ! defined( 'SULTSWRITEN_TIPS_ICON' ) ) {
	define( 'SULTSWRITEN_TIPS_ICON', '/sults/images/icones/produtos/modulo-checklist.webp' );
}

if ( ! defined( 'SULTSWRITEN_EXPORT_ZIP_PATH' ) ) {
	define( 'SULTSWRITEN_EXPORT_ZIP_PATH', 'sults/images/ilustracoes/secundaria/produtos/checklist/artigos/' );
}

if ( file_exists( SULTSWRITEN_PATH . 'vendor/autoload.php' ) ) {
	require SULTSWRITEN_PATH . 'vendor/autoload.php';
}

use Sults\Writen\Core\Plugin;
use Sults\Writen\Core\Activator;
use Sults\Writen\Core\Deactivator;
use Sults\Writen\Core\Uninstaller;

function sultswriten_run() {
	if ( class_exists( Plugin::class ) ) {
		( new Plugin() )->run();
	} elseif ( is_admin() ) {
			wp_die( 'ERRO FATAL SULTS: A classe Plugin não foi encontrada. Verifique se executou "composer install".' );
	}
}

register_activation_hook( SULTSWRITEN_FILE, array( Activator::class, 'activate' ) );
register_deactivation_hook( SULTSWRITEN_FILE, array( Deactivator::class, 'deactivate' ) );
register_uninstall_hook( SULTSWRITEN_FILE, array( Uninstaller::class, 'uninstall' ) );

sultswriten_run();
