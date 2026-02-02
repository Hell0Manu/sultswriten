<?php
/**
 * Definições Visuais dos Status.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\PostStatus
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\PostStatus;

/**
 * Classe StatusVisuals.
 *
 * Responsável por mapear cada status (slug) para um conjunto de cores
 * que será utilizado na interface administrativa (badges, botões de ação).
 *
 * Utiliza variáveis CSS nativas do plugin (definidas em `variables.css`)
 * para manter a consistência com o restante do design system.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\PostStatus
 * @author     Sults
 * @since      0.1.0
 */
class StatusVisuals {

	/**
	 * Retorna as definições visuais (cores de fundo e texto) para todos os status.
	 *
	 * Mapeia slugs de status (personalizados e nativos) para pares de cores.
	 *
	 * @since 0.1.0
	 * @return array Mapa [slug => ['bg' => cor, 'text' => cor]].
	 */
	public static function get_definitions(): array {
		return array(
			// --- FLUXO DE TEXTO ---
			StatusConfig::TEXT_IN_PROGRESS => array(
				'bg'   => 'var(--color-blue-500)', 
				'text' => 'var(--color-blue-100)',
			),
			StatusConfig::TEXT_REVIEW      => array(
				'bg'   => 'var(--color-orange-500)', 
				'text' => 'var(--color-orange-100)',
			),
			StatusConfig::TEXT_ADJUSTMENT  => array(
				'bg'   => 'var(--color-red-500)', 
				'text' => 'var(--color-red-100)',
			),

			// --- FLUXO DE IMAGENS ---
			StatusConfig::PENDING_IMAGE     => array(
				'bg'   => 'var(--color-purple-500)',
				'text' => 'var(--color-purple-100)',
			),
			StatusConfig::IMAGE_IN_PROGRESS => array(
				'bg'   => 'var(--color-blue-500)',
				'text' => 'var(--color-blue-100)',
			),
			StatusConfig::IMAGE_REVIEW      => array(
				'bg'   => 'var(--color-orange-500)',
				'text' => 'var(--color-orange-100)',
			),
			StatusConfig::IMAGE_ADJUSTMENT  => array(
				'bg'   => 'var(--color-red-500)',
				'text' => 'var(--color-red-100)',
			),

			// --- FLUXO FINAL ---
			StatusConfig::PENDING_PUBLICATION => array(
				'bg'   => 'var(--color-green-600)', 
				'text' => 'var(--color-green-100)',
			),
			StatusConfig::SUSPENDED           => array(
				'bg'   => 'var(--color-neutral-600)', 
				'text' => 'var(--color-neutral-100)',
			),

			// --- STATUS NATIVOS DO WP ---
			'publish' => array(
				'bg'   => 'var(--color-verdigris-500)', 
				'text' => 'var(--color-verdigris-100)',
			),
			'draft'   => array(
				'bg'   => 'var(--color-neutral-500)',
				'text' => 'var(--color-neutral-100)',
			),
			'pending' => array(
				'bg'   => 'var(--color-yellow-500)',
				'text' => 'var(--color-yellow-100)',
			),
			'future'  => array(
				'bg'   => 'var(--color-blue-500)',
				'text' => 'var(--color-blue-100)',
			),
			'private' => array(
				'bg'   => 'var(--color-neutral-800)',
				'text' => 'var(--color-neutral-100)',
			),
		);
	}

	/**
	 * Gera o bloco de CSS dinâmico para badges e botões.
	 *
	 * Cria regras CSS para:
	 * 1. Badges de status na listagem (`span.sults-status-{slug}`).
	 * 2. Botões de ação no workflow (`button.sults-workflow-btn.sults-status-{slug}`).
	 *
	 * @since 0.1.0
	 * @return string CSS minificado.
	 */
	public static function get_css_rules(): string {
		$css         = '';
		$definitions = self::get_definitions();

		foreach ( $definitions as $slug => $sults_style ) {
			// Sanitiza o slug para uso em classe CSS.
			$safe_slug = sanitize_html_class( $slug );
			$selector  = ".sults-status-{$safe_slug}";
			
			$bg   = $sults_style['bg'];
			$text = $sults_style['text'];

			// Regra para Badges (Listagem).
			$css .= "span{$selector} { background: {$bg}; color: {$text}; } ";

			// Regra para Botões (Painel de Workflow).
			$css .= "button.sults-workflow-btn{$selector} { 
				background-color: {$bg}; 
				color: {$text}; 
				border-color: {$bg};
				text-shadow: none;
			} ";
		}

		return $css;
	}
}