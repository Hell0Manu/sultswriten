<?php
/**
 * Implementação do renderizador de views simples.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @since      0.1.0
 */

namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\ViewRendererInterface;

/**
 * Classe SimpleViewRenderer.
 *
 * Responsável por incluir arquivos PHP como templates (views).
 * Recebe um array de dados e os transforma em variáveis locais acessíveis dentro do arquivo de view,
 * permitindo uma separação limpa entre lógica e apresentação.
 *
 * @see ViewRendererInterface
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Infrastructure
 * @author     Sults
 * @since      0.1.0
 */
class SimpleViewRenderer implements ViewRendererInterface {

	/**
	 * Caminho base para o diretório de views.
	 *
	 * @var string
	 */
	private string $base_path;

	/**
	 * Construtor.
	 *
	 * Define o diretório raiz onde o renderizador buscará os arquivos .php.
	 *
	 * @since 0.1.0
	 *
	 * @param string $base_path Caminho absoluto ou relativo para a pasta de views (ex: .../src/Interface/Dashboard/views/).
	 */
	public function __construct( string $base_path ) {
		// Garante que o caminho termine com uma barra para evitar erros de concatenação.
		$this->base_path = rtrim( $base_path, '/' ) . '/';
	}

	/**
	 * {@inheritDoc}
	 *
	 * Procura o arquivo `$view_name.php` dentro do diretório base.
	 * Se o arquivo existir, extrai os dados para o escopo local e faz o `require`.
	 * Caso contrário, emite um aviso (warning) para facilitar a depuração.
	 */
	public function render( string $view_name, array $data = array() ): void {
		$file_path = $this->base_path . $view_name . '.php';

		if ( ! file_exists( $file_path ) ) {
			// Trigger error ajuda a identificar views faltando sem quebrar a execução fatalmente.
			trigger_error( 'View não encontrada: ' . esc_html( $file_path ), E_USER_WARNING );
			return;
		}

		if ( ! empty( $data ) ) {
			// A função extract converte ['titulo' => 'Olá'] em uma variável $titulo = 'Olá'.
			extract( $data );
		}

		require $file_path;
	}
}