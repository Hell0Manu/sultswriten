<?php
/**
 * Transformador de Blocos de Arquivo (File Block).
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export\Transformers
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Export\Transformers;

use Sults\Writen\Contracts\DomTransformerInterface;
use Sults\Writen\Contracts\AttachmentProviderInterface;
use Sults\Writen\Contracts\ConfigProviderInterface;
use DOMDocument;
use DOMXPath;
use DOMElement;

/**
 * Classe FileBlockTransformer.
 *
 * Responsável por converter os blocos de arquivo do Gutenberg (`wp-block-file`)
 * em botões de download estilizados para o sistema legado.
 *
 * O bloco padrão do WP é apenas um link de texto. Este transformador o substitui
 * por um botão `<a>` com classes CSS específicas (`btn btn-rounded`), estilos inline
 * de gradiente (padrão Sults) e um ícone SVG de download anexado.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Export\Transformers
 * @author     Sults
 * @since      0.1.0
 */
class FileBlockTransformer implements DomTransformerInterface {

	/**
	 * Provedor de dados de anexos (para resolver URLs de arquivos).
	 * @var AttachmentProviderInterface
	 */
	private AttachmentProviderInterface $attachment_provider;

	/**
	 * Configurações gerais (para obter o caminho base de downloads).
	 * @var ConfigProviderInterface
	 */
	private ConfigProviderInterface $config;

	/**
	 * Construtor.
	 *
	 * @param AttachmentProviderInterface $attachment_provider Serviço de anexos.
	 * @param ConfigProviderInterface     $config              Serviço de configuração.
	 */
	public function __construct(
		AttachmentProviderInterface $attachment_provider,
		ConfigProviderInterface $config
	) {
		$this->attachment_provider = $attachment_provider;
		$this->config              = $config;
	}

	/**
	 * Transforma blocos de arquivo em botões Call-to-Action.
	 *
	 * Lógica:
	 * 1. Encontra `div.wp-block-file`.
	 * 2. Extrai o link de download (preferindo o botão oficial do bloco se existir).
	 * 3. Resolve o nome real do arquivo (lidando com URLs de anexo do WP).
	 * 4. Cria um novo elemento `<a>` com estilos inline e ícone SVG.
	 * 5. Substitui o bloco original pelo novo botão.
	 *
	 * @since 0.1.0
	 *
	 * @param DOMDocument $dom   O documento DOM.
	 * @param DOMXPath    $xpath O objeto XPath.
	 * @return void
	 */
	public function transform( DOMDocument $dom, DOMXPath $xpath ): void {
		$nodes     = $xpath->query( '//div[contains(@class, "wp-block-file")]' );
		$base_path = $this->config->get_downloads_base_path(); // Ex: /sults/downloads/

		foreach ( $nodes as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}

			// Encontra os links dentro do bloco.
			$links = $node->getElementsByTagName( 'a' );
			if ( $links->length === 0 ) {
				continue;
			}

			// Tenta encontrar o link que é explicitamente de download.
			$best_link = $links->item( 0 );
			foreach ( $links as $link ) {
				if ( $link instanceof DOMElement && $link->hasAttribute( 'download' ) ) {
					$best_link = $link;
					break;
				}
			}

			if ( ! $best_link instanceof DOMElement ) {
				continue;
			}

			$href     = $best_link->getAttribute( 'href' );
			$filename = basename( $href );

			// Correção para URLs "bonitas" do WP que não terminam em extensão (ex: /anexo/meu-pdf/).
			// Tenta resolver o ID do anexo para obter a URL real do arquivo físico.
			if ( strpos( $filename, '.' ) === false ) {
				$att_id = $this->attachment_provider->get_attachment_id_by_url( $href );

				if ( $att_id ) {
					$real_url = $this->attachment_provider->get_attachment_url( $att_id );
					if ( $real_url ) {
						$filename = basename( $real_url );
					}
				}
			}

			// Fallback de segurança se ainda não tiver extensão.
			if ( strpos( $filename, '.' ) === false ) {
				$filename = 'arquivo-download';
			}

			// Monta a URL final para o sistema legado.
			$final_href = $base_path . $filename;
			
			// Define o texto do botão (Usa o nome do arquivo se o texto for genérico 'Baixar').
			$text = trim( $best_link->textContent );
			if ( empty( $text ) || strtolower( $text ) === 'baixar' ) {
				$text = $filename;
			}

			// Cria o novo botão estilizado.
			$btn = $dom->createElement( 'a' );
			$btn->setAttribute( 'href', $final_href );
			$btn->setAttribute( 'download', '' );
			$btn->setAttribute( 'target', '_blank' );
			$btn->setAttribute( 'rel', 'noopener noreferrer' );
			$btn->setAttribute( 'class', 'btn btn-rounded call-to-action-btn primary' );
			
			// Estilos inline hardcoded conforme design system legado.
			$btn->setAttribute( 'style', 'background: linear-gradient(-20deg, #00acac 0, #00acac 100%); color: #ffffff; border: solid 2px #00acac; padding: 10px 24px; text-transform: initial; font-size: 1rem;' );

			$btn->appendChild( $dom->createTextNode( $text . ' ' ) );

			// Adiciona ícone SVG de download.
			$icon = $dom->createElement( 'img' );
			$icon->setAttribute( 'src', '/sults/images/icones/marca/download.svg' );
			$icon->setAttribute( 'width', '22' );
			$icon->setAttribute( 'height', '22' );
			$icon->setAttribute( 'style', 'margin-left:3px' );
			$icon->setAttribute( 'alt', 'Ícone Download' );

			$btn->appendChild( $icon );

			// Substitui o nó original no DOM.
			if ( $node->parentNode ) {
				$node->parentNode->replaceChild( $btn, $node );
			}
		}
	}
}