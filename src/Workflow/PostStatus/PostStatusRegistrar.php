<?php
/**
 * Registrador de Status Personalizados.
 *
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\PostStatus
 * @since      0.1.0
 */

namespace Sults\Writen\Workflow\PostStatus;

use Sults\Writen\Contracts\WPPostStatusProviderInterface;
use Sults\Writen\Workflow\Permissions\RoleDefinitions;
use Sults\Writen\Workflow\PostStatus\StatusConfig;

/**
 * Classe PostStatusRegistrar.
 *
 * Responsável por efetivar o registro dos status personalizados no WordPress.
 *
 * Lê as configurações da classe `StatusConfig` e utiliza o `WPPostStatusProvider`
 * para registrar cada status com seus respectivos labels e argumentos.
 *
 * Também fornece métodos auxiliares estáticos para recuperar listas de status
 * e regras de restrição de acesso.
 *
 * @see StatusConfig
 * @package    Sults\Writen
 * @subpackage Sults\Writen\Workflow\PostStatus
 * @author     Sults
 * @since      0.1.0
 */
class PostStatusRegistrar {

	/**
	 * Lista de roles que sofrem restrições de visualização/edição severas.
	 * Atualmente focado no 'Redator', que deve ter visão limitada do fluxo.
	 *
	 * @var array
	 */
	private const RESTRICTED_ROLES_LIST = array(
		RoleDefinitions::REDATOR,
	);

	/**
	 * Provedor de serviços de status do WP.
	 * @var WPPostStatusProviderInterface
	 */
	private WPPostStatusProviderInterface $status_provider;

	/**
	 * Construtor.
	 *
	 * @param WPPostStatusProviderInterface $status_provider Wrapper para funções de status do WP.
	 */
	public function __construct( WPPostStatusProviderInterface $status_provider ) {
		$this->status_provider = $status_provider;
	}

	/**
	 * Executa o registro dos status.
	 *
	 * Método de entrada chamado pelo `StatusManager` no hook `init`.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		$this->register_custom_statuses();
	}

	/**
	 * Loop principal de registro.
	 *
	 * Percorre a configuração global de status e registra no WordPress apenas
	 * aqueles que possuem `wp_args` definidos (status personalizados).
	 * Status nativos (Rascunho, Publicado) são ignorados pois já existem.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_custom_statuses(): void {
		$all_configs = StatusConfig::get_all();

		foreach ( $all_configs as $slug => $config ) {
			// Se não tiver argumentos WP, é um status nativo ou virtual. Pula.
			if ( empty( $config['wp_args'] ) ) {
				continue;
			}

			$args = $config['wp_args'];

			$sults_label   = $config['label'];
			$args['label'] = $sults_label;

			// Configura os labels de contagem (ex: "Em Andamento (5)") no topo da tabela de posts.
			// O uso de _n_noop permite compatibilidade com tradução futura.
			// phpcs:disable
			$args['label_count'] = _n_noop(
				$sults_label . ' <span class="count">(%s)</span>',
				$sults_label . ' <span class="count">(%s)</span>',
				'sultswriten'
			);
			// phpcs:enable

			$this->status_provider->register( $slug, $args );
		}
	}

	/**
	 * Retorna apenas os status personalizados registrados pelo plugin.
	 *
	 * Útil para filtrar queries ou gerar dropdowns, excluindo status nativos.
	 *
	 * @since 0.1.0
	 * @return array<string, string> Mapa [slug => label].
	 */
	public static function get_custom_statuses(): array {
		$output = array();
		foreach ( StatusConfig::get_all() as $slug => $config ) {
			if ( ! empty( $config['wp_args'] ) ) {
				$output[ $slug ] = $config['label'];
			}
		}
		return $output;
	}

	/**
	 * Retorna a lista de roles restritas.
	 *
	 * @since 0.1.0
	 * @return array
	 */
	public static function get_restricted_roles(): array {
		return self::RESTRICTED_ROLES_LIST;
	}

	/**
	 * Retorna a lista de slugs de status que possuem bloqueio de edição.
	 *
	 * Varre a configuração e extrai aqueles onde a regra `is_locked` é verdadeira.
	 *
	 * @since 0.1.0
	 * @return array Lista de slugs.
	 */
	public static function get_restricted_statuses(): array {
		$locked = array();
		foreach ( StatusConfig::get_all() as $slug => $config ) {
			if ( ! empty( $config['flow_rules']['is_locked'] ) ) {
				$locked[] = $slug;
			}
		}
		return $locked;
	}
}