<?php
/**
 * Container de Injeção de Dependência (DI).
 *
 * Uma implementação simples de Container para gerenciar a instanciação
 * e as dependências das classes do plugin.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Core
 * @since      0.1.0
 */

namespace Sults\Writen\Core;

final class Container {

	private array $definitions = array();
	private array $instances   = array();

	public function set( string $id, callable $factory ): void {
		$this->definitions[ $id ] = $factory;
	}

	public function get( string $id ) {
		if ( ! isset( $this->instances[ $id ] ) ) {
			$this->instances[ $id ] = ( $this->definitions[ $id ] )( $this );
		}

		return $this->instances[ $id ];
	}
}