<?php
namespace Sults\Writen\Contracts;

interface ViewRendererInterface {
    /**
     * Renderiza uma view localizada no diretório de templates.
     *
     * @param string $view_name O nome do arquivo (sem .php), ex: 'export-home'.
     * @param array  $data      Dados a serem passados para a view (chave => valor).
     */
    public function render( string $view_name, array $data = array() ): void;
}