<?php
/**
 * Helper para Manipulação de Hierarquias.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Utils
 * @since      0.1.0
 */

namespace Sults\Writen\Utils;

/**
 * Classe HierarchyHelper.
 *
 * Responsável por algoritmos de ordenação e estruturação de dados hierárquicos.
 *
 * Seu uso principal é transformar arrays planos de objetos (como o retorno de `get_categories`
 * ou `get_posts`) em listas lineares ordenadas por ancestralidade, injetando uma
 * propriedade `depth_level` que permite à UI renderizar a indentação correta.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Utils
 * @author     Sults
 * @since      0.1.0
 */
class HierarchyHelper {

	/**
	 * Transforma uma lista plana de objetos em uma lista ordenada hierarquicamente (Flat Tree).
	 *
	 * O algoritmo percorre a lista recursivamente. Para cada item encontrado que pertença
	 * ao pai atual, ele é adicionado à lista final e, imediatamente depois, o método
	 * busca seus filhos. Isso garante a ordem visual correta (Pai -> Filho -> Próximo Pai).
	 *
	 * @since 0.1.0
	 *
	 * @param array  $elements          Array de objetos (ex: WP_Post ou WP_Term).
	 * @param int    $sults_parent_id   ID do pai atual na iteração (padrão 0 para raiz).
	 * @param int    $depth             Nível de profundidade atual (usado para indentação).
	 * @param string $id_prop           Nome da propriedade de ID no objeto ('ID' ou 'term_id').
	 * @param string $sults_parent_prop Nome da propriedade de Pai no objeto ('post_parent' ou 'parent').
	 * @return array Array reordenado, onde cada objeto possui a nova propriedade ->depth_level.
	 */
	public static function build_hierarchy(
		array $elements,
		int $sults_parent_id = 0,
		int $depth = 0,
		string $id_prop = 'ID',
		string $sults_parent_prop = 'post_parent'
	): array {
		$ordered = array();

		foreach ( $elements as $element ) {
			// Verifica se o elemento atual é filho do pai que estamos buscando.
			if ( (int) $element->$sults_parent_prop === $sults_parent_id ) {

				// Marca a profundidade para uso no template (ex: str_repeat('— ', $depth)).
				$element->depth_level = $depth;
				$ordered[]            = $element;

				// Recursão: Busca os filhos deste elemento antes de passar para o próximo irmão.
				$children = self::build_hierarchy(
					$elements,
					(int) $element->$id_prop,
					$depth + 1,
					$id_prop,
					$sults_parent_prop
				);

				$ordered = array_merge( $ordered, $children );
			}
		}

		return $ordered;
	}
}