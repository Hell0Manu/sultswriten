<?php
namespace Sults\Writen\Workflow\PostStatus;

use Sults\Writen\Workflow\Permissions\RoleDefinitions;

class StatusConfig {

    // STATUS NATIVOS DO WP
    public const DRAFT   = 'draft';
    public const PENDING = 'pending';
    public const PUBLISH = 'publish';

    // STATUS DE TEXTO 
    public const TEXT_IN_PROGRESS = 'text_in_progress';
    public const TEXT_REVIEW      = 'text_review';
    public const TEXT_ADJUSTMENT  = 'text_adjustment';

    // STATUS DE IMAGEM
    public const PENDING_IMAGE     = 'pending_image';
    public const IMAGE_IN_PROGRESS = 'image_in_progress';
    public const IMAGE_REVIEW      = 'image_review';
    public const IMAGE_ADJUSTMENT  = 'image_adjustment';

    // STATUS FINAIS
    public const PENDING_PUBLICATION = 'pending_pub';
    public const SUSPENDED           = 'suspended';

    public static function get_all(): array {
        return array(
            // FLUXO INICIAL
            self::DRAFT => array(
                'label'      => 'Rascunho',
                'flow_rules' => array(
                    'is_locked'     => false,
                    'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE, RoleDefinitions::REDATOR ),
                ),
                'next_statuses' => array( self::TEXT_IN_PROGRESS, self::TEXT_REVIEW, self::SUSPENDED ),
            ),

            // FLUXO DE TEXTO
            self::TEXT_IN_PROGRESS => array( 
                'label'      => 'Texto em andamento',
                'wp_args'    => self::get_default_args(),
                'flow_rules' => array(
                    'is_locked'     => false,
                    'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE, RoleDefinitions::REDATOR ),
                ),
                'next_statuses' => array( self::TEXT_REVIEW, self::TEXT_IN_PROGRESS, self::SUSPENDED ),
            ),

            self::TEXT_REVIEW => array(
                'label'      => 'Revisão de texto',
                'wp_args'    => self::get_default_args(),
                'flow_rules' => array(
                    'is_locked'     => true,
                    'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE, RoleDefinitions::CORRETOR ),
                ),
                'next_statuses' => array( self::TEXT_ADJUSTMENT, self::PENDING_IMAGE, self::SUSPENDED ),
            ),

            self::TEXT_ADJUSTMENT => array(
                'label'      => 'Ajustar texto',
                'wp_args'    => self::get_default_args(),
                'flow_rules' => array(
                    'is_locked'     => false,
                    'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE, RoleDefinitions::REDATOR ),
                ),
                'next_statuses' => array( self::TEXT_IN_PROGRESS, self::TEXT_REVIEW, self::SUSPENDED ),
            ),

            // FLUXO DE IMAGEM
            self::PENDING_IMAGE => array(
                'label'      => 'Aguardando imagem',
                'wp_args'    => self::get_default_args(),
                'flow_rules' => array(
                    'is_locked'     => true,
                    'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE, RoleDefinitions::DESIGNER ),
                ),
                'next_statuses' => array( self::IMAGE_IN_PROGRESS, self::SUSPENDED ),
            ),

            self::IMAGE_IN_PROGRESS => array(
                'label'      => 'Imagem em andamento',
                'wp_args'    => self::get_default_args(),
                'flow_rules' => array(
                    'is_locked'     => false,
                    'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE, RoleDefinitions::DESIGNER ),
                ),
                'next_statuses' => array( self::IMAGE_REVIEW, self::SUSPENDED ),
            ),

            self::IMAGE_REVIEW => array(
                'label'      => 'Revisão de imagem',
                'wp_args'    => self::get_default_args(),
                'flow_rules' => array(
                    'is_locked'     => true,
                    'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE ),
                ),
                'next_statuses' => array( self::IMAGE_ADJUSTMENT, self::PENDING_PUBLICATION, self::SUSPENDED ),
            ),

            self::IMAGE_ADJUSTMENT => array(
                'label'      => 'Ajustar imagem',
                'wp_args'    => self::get_default_args(),
                'flow_rules' => array(
                    'is_locked'     => false,
                    'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE ),
                ),
                'next_statuses' => array( self::IMAGE_IN_PROGRESS, self::SUSPENDED ),
            ),
            
            // FLUXO FINAL 
            self::PENDING_PUBLICATION => array(
                'label'      => 'Aguardando publicação',
                'wp_args'    => self::get_default_args(),
                'flow_rules' => array(
                    'is_locked'     => true,
                    'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE ),
                ),
                'next_statuses' => array( self::PUBLISH, self::SUSPENDED ),
            ),

            self::PUBLISH => array(
                'label'      => 'Publicado',
                'flow_rules' => array(
                    'is_locked'     => true,
                    'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE ),
                ),
                'next_statuses' => array( self::SUSPENDED, self::DRAFT ), 
            ),

            self::SUSPENDED => array(
                'label'      => 'Suspenso',
                'wp_args'    => self::get_default_args( true ),
                'flow_rules' => array(
                    'is_locked'     => true,
                    'roles_allowed' => array( RoleDefinitions::ADMIN, RoleDefinitions::EDITOR_CHEFE ),
                ),
                'next_statuses' => array( self::DRAFT ),
            ),
        );
    }
    
    private static function get_default_args( bool $protected = false ): array {
        return array(
            'public'                    => false,
            'internal'                  => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'protected'                 => $protected,
        );
    }

    public static function get_config( string $slug ): array {
        $all = self::get_all();
        if ( isset( $all[ $slug ] ) ) {
            return $all[ $slug ];
        }
        return array(
            'label'         => get_post_status_object( $slug ) ? get_post_status_object( $slug )->label : $slug,
            'flow_rules'    => array( 'is_locked' => false, 'roles_allowed' => array() ),
            'next_statuses' => array(),
        );
    }
}
