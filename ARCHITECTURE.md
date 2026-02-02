# Arquitetura do Plugin Sults Writen

## 🚀 Ciclo de Vida
1. **Ponto de Entrada:** `sultswriten.php` define as constantes globais e instancia a classe `Sults\Writen\Core\Plugin`.
2. **Container (DI):** A classe `Core\Container` gerencia todas as instâncias do projeto, permitindo Injeção de Dependência.
3. **Service Providers:** O plugin é dividido em módulos através dos Providers (`src/Providers`):
   - `InfrastructureServiceProvider`: Registra serviços de baixo nível (Banco de dados, Arquivos).
   - `WorkflowServiceProvider`: Registra a lógica de negócio (Status, Exportação).
   - `DashboardServiceProvider`: Registra as páginas de administração e menus.

## 📁 Organização de Pastas
- `src/Core`: Classes fundamentais (Plugin, Container, Hooks).
- `src/Contracts`: Interfaces que definem o "contrato" de cada serviço.
- `src/Infrastructure`: Implementações que tocam o WordPress ou o sistema (WP_Post, Mailer).
- `src/Workflow`: O coração do plugin. Contém a lógica de exportação e fluxos de status.
- `src/Interface`: Tudo que o usuário vê (Menus, Views, Gutenberg).

## 🔄 Fluxo de Exportação
Quando um post é exportado:
`ExportController` -> `ExportProcessor` -> `HtmlExtractor` -> `Transformers (Image, Table, etc)` -> `JspBuilder`.