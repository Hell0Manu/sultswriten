<?php

namespace Sults\Writen\Utils;

class HierarchyHelper {

	/**
	 * Transforma uma lista plana de objetos (posts/categorias) em uma lista ordenada hierarquicamente
	 * com propriedade de profundidade (depth_level).
	 *
	 * @param array  $elements  Array de objetos (deve ter ID e parent_id/parent).
	 * @param int    $sults_parent_id ID do pai atual (para recursão).
	 * @param int    $depth     Nível de profundidade atual.
	 * @param string $id_prop   Nome da propriedade de ID (ex: 'ID' para posts, 'term_id' para cats).
	 * @param string $sults_parent_prop Nome da propriedade de Pai (ex: 'post_parent' para posts, 'parent' para cats).
	 * * @return array
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
			if ( (int) $element->$sults_parent_prop === $sults_parent_id ) {

				$element->depth_level = $depth;
				$ordered[]            = $element;

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
