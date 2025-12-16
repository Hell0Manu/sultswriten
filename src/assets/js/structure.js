jQuery(document).ready(function($) {
    const container = $('#sults-structure-app');
    const searchInput = $('#sults-search');

    if (typeof sultsStructureData === 'undefined' || sultsStructureData.length === 0) {
        container.html('<div class="notice notice-warning inline"><p>Nenhum conteúdo encontrado para exibir.</p></div>');
        return;
    }

    container.jstree({
        'core': {
            'data': sultsStructureData,
            'check_callback': true, // Permite mover
            'themes': {
                'name': 'default',
                'responsive': true,
                'variant': 'large', // Itens maiores
                'dots': false,      // DESLIGAMOS os dots padrão para usar nossas linhas CSS
                'icons': true,
                'stripes': false    // Controlamos zebrado via CSS
            }
        },
        'dnd': {
            'is_draggable': true,
            'check_while_dragging': true, // Melhora a resposta visual
            'large_drop_target': true     // Aumenta a área de soltar
        },
        'plugins': ['dnd', 'search', 'types', 'wholerow'],
        'types': {
            'default': {
                'icon': 'dashicons dashicons-admin-post'
            }
        }
    });

    // --- Lógica de Busca e AJAX (Mantém a mesma do passo anterior) ---
    let to = false;
    searchInput.keyup(function () {
        if(to) { clearTimeout(to); }
        to = setTimeout(function () {
            var v = searchInput.val();
            container.jstree(true).search(v);
        }, 250);
    });

 // ... (código anterior)

    // Evento: Ao terminar de arrastar
    container.on('move_node.jstree', function (e, data) {
        let parentId = data.parent;
        if (parentId === '#') parentId = 0;

        // Recupera a nova ordem dos filhos desse pai
        // data.instance = instância da árvore
        // get_node(parentId).children retorna array de IDs na ordem visual
        var siblings = data.instance.get_node(data.parent).children;

        // Feedback visual (opcional)
        // container.css('opacity', '0.6');

        $.ajax({
            url: sultsStructureParams.ajax_url,
            type: 'POST',
            data: {
                action: 'sults_update_structure',
                security: sultsStructureParams.nonce,
                post_id: data.node.id,
                parent_id: parentId,
                order: siblings // <--- ENVIAMOS A LISTA DE ORDEM
            },
            success: function(response) {
                // container.css('opacity', '1');
                if (!response.success) {
                    alert('Erro ao salvar: ' + (response.data || 'Erro desconhecido'));
                    location.reload(); 
                }
            },
            error: function() {
                // container.css('opacity', '1');
                alert('Erro de conexão.');
            }
        });
    });
});