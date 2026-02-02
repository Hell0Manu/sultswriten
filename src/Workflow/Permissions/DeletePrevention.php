<?php
/**
 * Prevenção de Exclusão Permanente.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Permissions;

use Sults\Writen\Workflow\Permissions\RoleDefinitions;

/**
 * Classe DeletePrevention.
 *
 * Responsável por implementar uma "trava de segurança" na lixeira.
 *
 * O objetivo é impedir que usuários, mesmo com permissões elevadas como 'Editor-Chefe',
 * excluam permanentemente um post (esvaziar lixeira).
 * A exclusão definitiva fica restrita apenas aos Administradores do sistema.
 *
 * Isso evita a perda acidental de conteúdo que poderia ser recuperado.
 *
 * @see RoleDefinitions
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @author     Sults
 * @since      0.1.0
 */
class DeletePrevention {

	/**
	 * Registra o filtro de verificação de capacidade.
	 *
	 * Utiliza `map_meta_cap` para interceptar a verificação de permissão no momento exato
	 * em que o WordPress decide se o usuário pode deletar o post.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		add_filter( 'map_meta_cap', array( $this, 'prevent_permanent_delete' ), 10, 4 );
	}

	/**
	 * Impede a exclusão permanente de itens na lixeira.
	 *
	 * Lógica:
	 * 1. Verifica se a ação é `delete_post`.
	 * 2. Verifica se o post JÁ está na lixeira (`post_status === 'trash'`).
	 * 3. Se estiver na lixeira, e o usuário for um Editor-Chefe, nega a permissão.
	 *
	 * Resultado: O Editor-Chefe pode mover para a lixeira, mas o botão "Excluir Permanentemente"
	 * sumirá ou falhará para ele.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $caps    Capacidades requeridas.
	 * @param string $cap     Nome da capacidade sendo checada ('delete_post').
	 * @param int    $user_id ID do usuário atual.
	 * @param array  $args    Argumentos extras (ID do post).
	 * @return array Array de capacidades (pode retornar ['do_not_allow']).
	 */
	public function prevent_permanent_delete( array $caps, string $cap, int $user_id, array $args ): array {
		// Apenas intercepta a capacidade de deletar.
		if ( 'delete_post' !== $cap ) {
			return $caps;
		}

		$sults_post_id = isset( $args[0] ) ? $args[0] : 0;
		if ( ! $sults_post_id ) {
			return $caps;
		}

		// A verificação só ocorre se o post já estiver na lixeira (tentativa de exclusão definitiva).
		if ( get_post_status( $sults_post_id ) === 'trash' ) {
			$user = get_userdata( $user_id );

			// Se for Editor-Chefe, bloqueia. Apenas Admin passa.
			if ( $user && in_array( RoleDefinitions::EDITOR_CHEFE, (array) $user->roles, true ) ) {
				return array( 'do_not_allow' );
			}
		}

		return $caps;
	}
}