<?php
/**
 * Limitador de Acesso à Biblioteca de Mídia.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\Permissions;

use Sults\Writen\Contracts\WPUserProviderInterface;
use Sults\Writen\Workflow\Permissions\RoleDefinitions;

/**
 * Classe MediaLibraryLimiter.
 *
 * Responsável por restringir a visão da biblioteca de mídia no modal de upload.
 *
 * Garante que usuários com o papel de 'Redator' vejam apenas os arquivos
 * que eles mesmos enviaram. Isso mantém a organização e a privacidade,
 * evitando que redatores usem imagens de outros artigos ou vejam assets não aprovados.
 *
 * @see RoleDefinitions
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\Permissions
 * @author     Sults
 * @since      0.1.0
 */
class MediaLibraryLimiter {

	/**
	 * Provedor de dados do usuário.
	 * @var WPUserProviderInterface
	 */
	private WPUserProviderInterface $user_provider;

	/**
	 * Construtor.
	 *
	 * @param WPUserProviderInterface $user_provider Serviço para obter roles do usuário.
	 */
	public function __construct( WPUserProviderInterface $user_provider ) {
		$this->user_provider = $user_provider;
	}

	/**
	 * Registra o filtro na query de anexos via AJAX.
	 *
	 * O hook `ajax_query_attachments_args` afeta especificamente o modal de mídia
	 * que abre quando se clica em "Adicionar Mídia" ou "Imagem Destacada" no editor.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		add_filter( 'ajax_query_attachments_args', array( $this, 'limit_media_library' ) );
	}

	/**
	 * Aplica a restrição de autor na query de mídia.
	 *
	 * Se o usuário for Redator, força o parâmetro `author` da query para ser o ID dele.
	 *
	 * @since 0.1.0
	 *
	 * @param array $query Argumentos da query de anexos (WP_Query args).
	 * @return array Argumentos modificados.
	 */
	public function limit_media_library( array $query ): array {
		$user_roles = $this->user_provider->get_current_user_roles();
		$user       = wp_get_current_user();

		// Se for Redator, restringe a visão aos seus próprios uploads.
		if ( in_array( RoleDefinitions::REDATOR, $user_roles, true ) ) {
			$query['author'] = $user->ID;
		}

		return $query;
	}
}