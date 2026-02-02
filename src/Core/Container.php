<?php
/**
 * Define o Container de Injeção de Dependência.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Core
 * @since      0.1.0
 */

namespace Sults\Writen\Core;

/**
 * Container de Injeção de Dependência (DI).
 *
 * Esta classe gerencia o registro e a instanciação de serviços do plugin.
 * Ela armazena as definições (como criar um objeto) e as instâncias já criadas (cache/singleton),
 * garantindo que dependências compartilhadas sejam reutilizadas em todo o sistema.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Core
 * @since      0.1.0
 * @final
 */
final class Container {

	/**
	 * Armazena as definições de fábrica (factories) para os serviços.
	 *
	 * A chave é o identificador do serviço (geralmente o nome da classe ou interface)
	 * e o valor é uma função anônima (closure) que sabe criar esse serviço.
	 *
	 * @since 0.1.0
	 * @var array<string, callable>
	 */
	private array $definitions = array();

	/**
	 * Armazena as instâncias já criadas dos serviços (Singleton Cache).
	 *
	 * Se um serviço já foi solicitado antes, ele estará aqui para ser retornado
	 * imediatamente, sem precisar ser recriado.
	 *
	 * @since 0.1.0
	 * @var array<string, object>
	 */
	private array $instances   = array();

	/**
	 * Registra um serviço no container.
	 *
	 * Define como uma classe deve ser instanciada, mas NÃO a cria agora (Lazy Loading).
	 * A criação só acontece quando o método `get()` for chamado pela primeira vez.
	 *
	 * @since 0.1.0
	 *
	 * @param string   $id      O identificador único do serviço (ex: MinhaClasse::class).
	 * @param callable $factory Uma função que recebe o Container e retorna a instância do serviço.
	 * Assinatura: function(Container $c): object
	 * @return void
	 */
	public function set( string $id, callable $factory ): void {
		$this->definitions[ $id ] = $factory;
	}

	/**
	 * Recupera uma instância de um serviço.
	 *
	 * Se a instância já existir, retorna a mesma (Singleton).
	 * Se não existir, executa a factory registrada em `set()`, salva o resultado e retorna.
	 *
	 * @since 0.1.0
	 *
	 * @template T
	 * @param string|class-string<T> $id O identificador do serviço a ser recuperado.
	 * @return object|T A instância do serviço solicitado.
	 * @throws \Exception (Implícito) Pode lançar erro se o ID não tiver sido registrado em $definitions.
	 */
	public function get( string $id ) {
		if ( ! isset( $this->instances[ $id ] ) ) {
			// Executa a factory passando o próprio container ($this)
			// para resolver dependências recursivas.
			$this->instances[ $id ] = ( $this->definitions[ $id ] )( $this );
		}

		return $this->instances[ $id ];
	}
}
