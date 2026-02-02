<?php
/**
 * Controlador de Visibilidade na Listagem de Posts.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Permissions;

use Sults\Writen\Workflow\Permissions\VisibilityPolicy;

/**
 * Classe PostListVisibility.
 *
 * Responsável por interceptar a query principal do WordPress no Admin (`edit.php`)
 * e modificar a cláusula SQL `WHERE` para restringir quais posts o usuário vê.
 *
 * Objetivo principal:
 * Impedir que usuários com papéis restritos (ex: Redatores) vejam os rascunhos
 * uns dos outros, mantendo a privacidade do trabalho em progresso, mas permitindo
 * a visualização de posts que já avançaram no fluxo (conforme definido na Policy).
 *
 * @see VisibilityPolicy
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @author     Sults
 * @since      0.1.0
 */
class PostListVisibility {

	/**
	 * Política de visibilidade.
	 * Define as regras de quem pode ver o quê.
	 *
	 * @var VisibilityPolicy
	 */
	private VisibilityPolicy $visibility_policy;

	/**
	 * Construtor.
	 *
	 * @param VisibilityPolicy $visibility_policy Instância da política de regras.
	 */
	public function __construct( VisibilityPolicy $visibility_policy ) {
		$this->visibility_policy = $visibility_policy;
	}

	/**
	 * Registra o filtro de modificação de query.
	 *
	 * Utiliza prioridade alta (99) para garantir que esta restrição seja aplicada
	 * por último, sobrepondo eventuais modificações de outros plugins.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		add_filter( 'posts_where', array( $this, 'restrict_post_list_visibility' ), 99, 2 );
	}

	/**
	 * Aplica filtro SQL WHERE se o usuário for restrito.
	 *
	 * Lógica da Restrição:
	 * Se o usuário não tem permissão de ver tudo (ex: não é Editor/Admin),
	 * a query é alterada para retornar apenas:
	 * 1. Posts onde ele é o autor (`post_author = ID`).
	 * 2. OU Posts que estão em status "públicos" para a equipe (ex: Em Revisão).
	 *
	 * @since 0.1.0
	 *
	 * @param string    $where Cláusula WHERE atual da query SQL.
	 * @param \WP_Query $query Objeto da query sendo executada.
	 * @return string Cláusula WHERE modificada com as restrições de segurança.
	 */
	public function restrict_post_list_visibility( string $where, \WP_Query $query ): string {
		global $wpdb;

		// Aplica apenas no Admin e na Query Principal da listagem.
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return $where;
		}

		// Aplica apenas para o post type 'post'.
		if ( 'post' !== $query->get( 'post_type' ) ) {
			return $where;
		}

		// Verifica na política se o usuário precisa de restrição.
		// Se ele pode ver posts de outros (ex: Editor), retorna sem alterar nada.
		if ( $this->visibility_policy->can_see_others_posts() ) {
			return $where;
		}

		$current_user_id  = get_current_user_id();
		$allowed_statuses = $this->visibility_policy->get_allowed_statuses_for_restricted_user();

		// Prepara placeholders (%s) para a lista de status permitidos.
		$sults_placeholders = implode( ', ', array_fill( 0, count( $allowed_statuses ), '%s' ) );

		// Monta a query segura: (Sou Autor OU Status é Permitido).
		$query_template = " AND ( 
            {$wpdb->posts}.post_author = %d 
            OR {$wpdb->posts}.post_status IN ( $sults_placeholders )
        )";

		// Combina o ID do usuário e os status em um único array de argumentos.
		$args = array_merge( array( $current_user_id ), $allowed_statuses );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query dinâmica construída com placeholders seguros acima.
		$sql_restriction = $wpdb->prepare( $query_template, ...$args );

		$where .= $sql_restriction;

		return $where;
	}
}