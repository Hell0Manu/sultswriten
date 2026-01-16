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
			// Status Personalizados.
			StatusConfig::SUSPENDED           => array(
				'bg'   => 'var(--color-red-500)',
				'text' => 'var(--color-red-100)',
			),
			StatusConfig::REQUIRES_ADJUSTMENT => array(
				'bg'   => 'var(--color-orange-500)',
				'text' => 'var(--color-orange-100)',
			),
			StatusConfig::REVIEW_IN_PROGRESS  => array(
				'bg'   => 'var(--color-blue-500)',
				'text' => 'var(--color-blue-100)',
			),
			StatusConfig::FINISHED            => array(
				'bg'   => 'var(--color-green-500)',
				'text' => 'var(--color-green-100)',
			),
			StatusConfig::PENDING_IMAGE       => array(
				'bg'   => 'var(--color-purple-500)',
				'text' => 'var(--color-purple-100)',
			),
			// Status Nativos (Defaults).
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
	 * Gera as regras CSS para todos os status.
	 */
	public static function get_css_rules(): string {
		$css         = '';
		$definitions = self::get_definitions();

		foreach ( $definitions as $slug => $sults_style ) {
			$selector = ".sults-status-{$slug}";
			$bg       = $sults_style['bg'];
			$text     = $sults_style['text'];

			$css .= "{$selector} { background: {$bg}; color: {$text}; } ";
		}

		return $css;
	}
}
