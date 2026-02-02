<?php
/**
 * Transformador de Blocos de Dicas (Sults Tips).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export\Transformers
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Export\Transformers;

use Sults\Writen\Contracts\DomTransformerInterface;
use Sults\Writen\Contracts\ConfigProviderInterface;
use DOMDocument;
use DOMXPath;
use DOMNode;

/**
 * Classe SultsTipTransformer.
 *
 * Responsável por padronizar os blocos de destaque "Dica SULTS".
 *
 * Este transformador converte estruturas HTML que foram usadas (possivelmente de forma legada)
 * para representar dicas em um componente `<aside>` semântico e padronizado visualmente.
 *
 * Converte:
 * 1. Tags `<pre>` (assumindo que foram usadas para destacar texto).
 * 2. Tags `<table>` que contêm explicitamente o texto "Dica SULTS".
 *
 * Saída:
 * `<aside class="dica-sults"><img icon><div><h3>Dica SULTS</h3><p>Conteúdo</p></div></aside>`
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export\Transformers
 * @author     Sults
 * @since      0.1.0
 */
class SultsTipTransformer implements DomTransformerInterface {

	/**
	 * Configurações gerais (para obter caminhos de ícones).
	 * @var ConfigProviderInterface
	 */
	private ConfigProviderInterface $config;

	/**
	 * Construtor.
	 *
	 * @param ConfigProviderInterface $config Serviço de configuração.
	 */
	public function __construct( ConfigProviderInterface $config ) {
		$this->config = $config;
	}

	/**
	 * Executa a transformação de dicas.
	 *
	 * @since 0.1.0
	 *
	 * @param DOMDocument $dom   O documento DOM.
	 * @param DOMXPath    $xpath O objeto XPath.
	 * @return void
	 */
	public function transform( DOMDocument $dom, DOMXPath $xpath ): void {
		// 1. Converte todas as tags <pre>.
		// Nota: Isso assume que <pre> não é usado para código neste contexto editorial,
		// mas sim como uma caixa de destaque simples.
		$sults_pre_nodes = $xpath->query( '//pre' );
		foreach ( $sults_pre_nodes as $node ) {
			$this->replace_with_tip( $dom, $node, $node->nodeValue );
		}

		// 2. Converte tabelas que simulam layout de dica.
		$tables = $xpath->query( '//table' );
		foreach ( $tables as $table ) {
			// Verifica se é uma "tabela de dica" procurando pelo texto mágico.
			if ( stripos( $table->textContent, 'Dica SULTS' ) !== false ) {

				// Limpa o prefixo "Dica SULTS:" para não duplicar no título novo.
				$clean_text = str_ireplace( array( 'Dica SULTS:', 'Dica SULTS' ), '', $table->textContent );
				$clean_text = trim( $clean_text );

				$this->replace_with_tip( $dom, $table, $clean_text );
			}
		}
	}

	/**
	 * Helper para construir o componente visual da Dica e substituir o nó antigo.
	 *
	 * Monta a estrutura HTML:
	 * <aside class="dica-sults">
	 * <img src="..." alt="Dica SULTS">
	 * <div>
	 * <h3>Dica SULTS</h3>
	 * <p>Conteúdo...</p>
	 * </div>
	 * </aside>
	 *
	 * @param DOMDocument $dom         O documento DOM.
	 * @param DOMNode     $target_node O nó original a ser substituído (pre ou table).
	 * @param string      $text_content O texto extraído para o corpo da dica.
	 */
	private function replace_with_tip( DOMDocument $dom, DOMNode $target_node, string $text_content ): void {
		$aside = $dom->createElement( 'aside' );
		$aside->setAttribute( 'class', 'dica-sults' );

		// Ícone da lâmpada/dica.
		$img       = $dom->createElement( 'img' );
		$icon_path = $this->config->get_tips_icon_path();

		// Fallback para constante global se config falhar.
		if ( empty( $icon_path ) ) {
			$icon_path = SULTSWRITEN_TIPS_ICON;
		}

		$img->setAttribute( 'src', $icon_path );
		$img->setAttribute( 'alt', 'Dica SULTS' );
		$img->setAttribute( 'loading', 'lazy' );
		$img->setAttribute( 'width', '60' );
		$img->setAttribute( 'height', '59' );

		$aside->appendChild( $img );

		// Container de texto.
		$div = $dom->createElement( 'div' );

		$h3 = $dom->createElement( 'h3', 'Dica SULTS' );
		$div->appendChild( $h3 );

		$sults_p = $dom->createElement( 'p' );
		$sults_p->appendChild( $dom->createTextNode( $text_content ) );
		$div->appendChild( $sults_p );

		$aside->appendChild( $div );

		// Substituição no DOM.
		if ( $target_node->parentNode ) {
			$target_node->parentNode->replaceChild( $aside, $target_node );
		}
	}
}