<?php
namespace Sults\Writen\Workflow\PostStatus;

class StatusVisuals {

	/**
	 * Retorna as definições visuais (cores) para os status.
	 *
	 * @return array
	 */
	public static function get_definitions(): array {
		return array(
			// FLUXO DE TEXTO
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

			// FLUXO DE IMAGENS
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

			// FLUXO FINAL
			StatusConfig::PENDING_PUBLICATION => array(
				'bg'   => 'var(--color-green-600)', 
				'text' => 'var(--color-green-100)',
			),
			StatusConfig::SUSPENDED           => array(
				'bg'   => 'var(--color-neutral-600)', 
				'text' => 'var(--color-neutral-100)',
			),

			// STATUS NATIVOS
			'publish'                         => array(
				'bg'   => 'var(--color-verdigris-500)', 
				'text' => 'var(--color-verdigris-100)',
			),
			'draft'                           => array(
				'bg'   => 'var(--color-neutral-500)',
				'text' => 'var(--color-neutral-100)',
			),
			'pending'                         => array(
				'bg'   => 'var(--color-yellow-500)',
				'text' => 'var(--color-yellow-100)',
			),
			'future'                          => array(
				'bg'   => 'var(--color-blue-500)',
				'text' => 'var(--color-blue-100)',
			),
			'private'                         => array(
				'bg'   => 'var(--color-neutral-800)',
				'text' => 'var(--color-neutral-100)',
			),
		);
	}

/**
	 * Gera as regras CSS para badges e botões.
	 */
	public static function get_css_rules(): string {
		$css         = '';
		$definitions = self::get_definitions();

		foreach ( $definitions as $slug => $sults_style ) {
			$selector = ".sults-status-{$slug}";
			$bg       = $sults_style['bg'];
			$text     = $sults_style['text'];

			$css .= "span{$selector} { background: {$bg}; color: {$text}; } ";

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