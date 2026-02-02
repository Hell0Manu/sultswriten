<?php
/**
 * Implementação do arquivador ZIP.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */

namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\ArchiverInterface;
use ZipArchive;

/**
 * Classe ZipArchiver.
 *
 * Responsável por criar arquivos compactados (.zip) contendo os arquivos físicos
 * (como imagens) e os arquivos virtuais (como o JSP gerado em memória).
 *
 * Esta implementação utiliza a classe nativa `ZipArchive` do PHP e inclui
 * uma verificação de segurança para garantir que a extensão esteja habilitada.
 *
 * @see ArchiverInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */
class ZipArchiver implements ArchiverInterface {

	/**
	 * Cria um arquivo ZIP com os arquivos e strings fornecidos.
	 *
	 * @since 0.1.0
	 *
	 * @param string $output_path O caminho completo onde o arquivo ZIP será salvo.
	 * @param array  $files_map   Mapa de arquivos reais para caminhos dentro do ZIP.
	 * Ex: ['/var/www/img.jpg' => 'assets/img.jpg']
	 * @param array  $string_map  Mapa de nomes de arquivos para conteúdos de strings (arquivos virtuais).
	 * Ex: ['index.jsp' => '<html>...</html>']
	 * @return bool Retorna true se o arquivo ZIP foi criado com sucesso, false caso contrário.
	 */
	public function create( string $output_path, array $files_map, array $string_map ): bool {
		// Verifica se a extensão ZIP está habilitada no servidor.
		if ( ! class_exists( 'ZipArchive' ) ) {
			return false;
		}

		$zip = new ZipArchive();

		// Tenta criar o arquivo (sobrescreve se já existir).
		if ( $zip->open( $output_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
			return false;
		}

		// Adiciona arquivos físicos (do disco).
		foreach ( $files_map as $real_path => $zip_path ) {
			if ( file_exists( $real_path ) ) {
				$zip->addFile( $real_path, $zip_path );
			}
		}

		// Adiciona arquivos virtuais (da memória).
		foreach ( $string_map as $filename => $content ) {
			$zip->addFromString( $filename, $content );
		}

		return $zip->close();
	}
}