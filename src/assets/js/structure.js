jQuery(document).ready(function($) {
    const wrapper = $('.sults-structure-wrapper');

    wrapper.on('click', '.sults-toggle', function(e) {
        e.preventDefault();
        const icon = $(this);
        const li = icon.closest('li.sults-item');
        
        li.toggleClass('sults-closed');
        
        if (li.hasClass('sults-closed')) {
            icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
        } else {
            icon.removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
        }
    });

    const canManage = Boolean(Number(sultsStructureParams.can_manage));

    if (canManage) {
        wrapper.addClass('sults-can-manage');
        initSortable();
    } else {
        wrapper.addClass('sults-readonly');
    }

    function initSortable() {
        $('.sults-sortable-root, .sults-sortable-nested').sortable({
            connectWith: '.sults-sortable-root, .sults-sortable-nested', 
            handle: '.sults-handle', 
            placeholder: 'sults-placeholder',
            tolerance: 'pointer',
            cursor: 'grabbing',
            
            stop: function(event, ui) {
                const item = ui.item;
                const itemId = item.data('id');
                const parentUl = item.parent();
                
                let parentId = 0;
                if (parentUl.hasClass('sults-sortable-nested')) {
                    parentId = parentUl.closest('li.sults-item').data('id');
                }

                const siblings = parentUl.sortable('toArray', { attribute: 'data-id' });
                saveStructure(itemId, parentId, siblings);
            }
        });
    }

    function saveStructure(postId, parentId, orderArray) {
        $.ajax({
            url: sultsStructureParams.ajax_url,
            type: 'POST',
            data: {
                action: 'sults_update_structure',
                security: sultsStructureParams.nonce,
                post_id: postId,
                parent_id: parentId,
                order: orderArray
            },
            success: function(res) {
                if(!res.success) alert('Erro ao salvar.');
            }
        });
    }
});