<?php
namespace Sults\Writen\Workflow\PostStatus;

use Sults\Writen\Workflow\Permissions\RoleDefinitions;

class StatusConfig {
    public const SUSPENDED           = 'suspended';
    public const REQUIRES_ADJUSTMENT = 'requires_adjustment';
    public const REVIEW_IN_PROGRESS  = 'review_in_progress';
    public const FINISHED            = 'finished';
    
    public const DRAFT               = 'draft';
    public const PENDING             = 'pending';
    public const PUBLISH             = 'publish';

    public static function get_all(): array {
        return array(
            self::SUSPENDED => array(
                'label'       => 'Suspenso',
                'css_class'   => 'sults-status-suspended',
                'wp_args'     => array(
                    'public'                    => false,
                    'internal'                  => false,
                    'exclude_from_search'       => true,
                    'show_in_admin_all_list'    => true,
                    'show_in_admin_status_list' => true,
                    'protected'                 => true,
                ),
                'flow_rules'  => array(
                    'is_locked'      => true,
                    'roles_allowed'  => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE ),
                ),
            ), 
            self::REQUIRES_ADJUSTMENT => array(
                'label'       => 'Precisa de Ajustes',
                'css_class'   => 'sults-status-requires_adjustment',
                'wp_args'     => array(
                    'public'                    => false,
                    'exclude_from_search'       => true,
                    'show_in_admin_all_list'    => true,
                    'show_in_admin_status_list' => true,
                    'protected'                 => true,
                ),
                'flow_rules'  => array(
                    'is_locked'      => false,
                    'roles_allowed'  => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE, RoleDefinitions::REDATOR ),
                ),
            ),
            self::REVIEW_IN_PROGRESS => array(
                'label'       => 'Revisão em andamento',
                'css_class'   => 'sults-status-revisao_em_andamento',
                'wp_args'     => array(
                    'public'                    => false,
                    'exclude_from_search'       => true,
                    'show_in_admin_all_list'    => true,
                    'show_in_admin_status_list' => true,
                    'protected'                 => true,
                ),
                'flow_rules'  => array(
                    'is_locked'      => true,
                    'roles_allowed'  => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE ),
                ),
            ),
            self::FINISHED => array(
                'label'       => 'Finalizado',
                'css_class'   => 'sults-status-finished',
                'wp_args'     => array(
                    'public'                    => false,
                    'exclude_from_search'       => true,
                    'show_in_admin_all_list'    => true,
                    'show_in_admin_status_list' => true,
                    'protected'                 => true,
                ),
                'flow_rules'  => array(
                    'is_locked'      => true,
                    'roles_allowed'  => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE ),
                ),
            ),

        );
    }

    public static function get_config( string $slug ): array {
        $all = self::get_all();

        if ( isset( $all[ $slug ] ) ) {
            return $all[ $slug ];
        }

        return array(
            'label'     => get_post_status_object( $slug ) ? get_post_status_object( $slug )->label : $slug,
            'css_class' => 'sults-status-' . $slug,
            'flow_rules' => array(
                'is_locked'     => false,
                'roles_allowed' => array(),
            ),
        );
    }
}