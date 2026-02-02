<?php
/**
 * Desativador de Geração de Miniaturas.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Media
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Media;

use Sults\Writen\Contracts\HookableInterface;

/**
 * Classe ThumbnailDisabler.
 *
 * Responsável por impedir que o WordPress gere automaticamente múltiplas versões
 * redimensionadas (thumbnails, medium, large) para cada imagem enviada.
 *
 * Motivação:
 * No contexto do Sults Writen, o WordPress atua primariamente como repositório de conteúdo bruto.
 * A geração de múltiplos tamanhos de imagem consome espaço em disco desnecessariamente e
 * aumenta o tempo de upload, visto que o sistema legado (destino da exportação)
 * geralmente consome a imagem original ou possui seu próprio pipeline de tratamento.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Media
 * @author     Sults
 * @since      0.1.0
 */
class ThumbnailDisabler implements HookableInterface {

	/**
	 * Registra os filtros para desativar o redimensionamento.
	 *
	 * Utiliza funções nativas do WordPress (`__return_empty_array`, `__return_false`)
	 * como callbacks para simplificar a implementação.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		// Remove todos os tamanhos de imagem intermediários registrados.
		// Resulta em apenas o arquivo original sendo salvo no disco.
		add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array' );

		// Desativa o "Big Image Threshold".
		// Por padrão, o WP escala imagens maiores que 2560px. Isso desativa esse comportamento,
		// mantendo a imagem original intacta, não importa o tamanho.
		add_filter( 'big_image_size_threshold', '__return_false' );
	}
}
