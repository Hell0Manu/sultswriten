jQuery(document).ready(function($) {
    const wrapper = $('.sults-structure-wrapper');
    
    const $drawer = $('#sults-detail-drawer');
    const $backdrop = $('#sults-drawer-backdrop');
    const $drawerBody = $drawer.find('.sults-drawer-body');
    const $loadingState = $drawer.find('.sults-drawer-loading');
    const $contentState = $drawer.find('.sults-drawer-content');


/* =========================================
      MODAL DE CRIAÇÃO
       ========================================= */
    const $modalBackdrop = $('#sults-modal-backdrop');
    const $form = $('#sults-create-post-form');
    const $titleInput = $('#new-post-title');
    const $slugInput = $('#new-post-slug');
    
    const $parentSelect = $('#new-post-parent');
    const $parentGroup = $parentSelect.closest('.sults-form-group');
    
    const $sidebarSelect = $('#new-post-sidebar');
    const $sidebarGroup = $sidebarSelect.closest('.sults-form-group');

    const $categorySelect = $('#new-post-category');
    const $slugPrefix = $('#new-post-slug-prefix'); 

    const $allParentOptions = $parentSelect.find('option').clone();

    $('#btn-open-new-post').on('click', function(e) {
        e.preventDefault();
        $form[0].reset();
        
        $slugPrefix.text('/');
        $('#hidden-cat-id').remove();
        
        $parentGroup.hide(); 
        $sidebarGroup.hide(); 
        
        $categorySelect.val(""); 

        $modalBackdrop.addClass('open');
        $titleInput.focus();
    });

    $('.sults-modal-close, .sults-modal-cancel, #sults-modal-backdrop').on('click', function(e) {
        if (e.target !== this) return;
        $modalBackdrop.removeClass('open');
    });

    $categorySelect.on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const slug = selectedOption.data('slug'); 
        const selectedCatId = $(this).val();
        
        if (slug) {
            $slugPrefix.text('/' + slug + '/');
        } else {
            $slugPrefix.text('/');
        }

        if (slug === 'checklist') {
            $sidebarGroup.fadeIn(200);
        } else {
            $sidebarGroup.hide();
            $sidebarSelect.val('0'); 
        }

        if (selectedCatId) {
            $parentSelect.empty();
            
            const rootOption = $allParentOptions.filter('[value="0"]');
            $parentSelect.append(rootOption);

            const matchingParents = $allParentOptions.filter(function() {
                return String($(this).data('cat-id')) === String(selectedCatId);
            });
            
            $parentSelect.append(matchingParents);
            $parentSelect.val("0");

            $parentGroup.fadeIn(200);
        } else {
            $parentGroup.hide();
        }
    });

    $titleInput.on('input', function() {
        const val = $(this).val();
        const slug = val.toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/--+/g, '-');
        $slugInput.val(slug);
    });

    $form.on('submit', function(e) {
        e.preventDefault();
        
        let formData = $(this).serialize();
        const $btn = $(this).find('button[type="submit"]');
        
        $btn.prop('disabled', true).text('Criando...');

        $.ajax({
            url: sultsStructureParams.ajax_url,
            type: 'POST',
            data: formData + '&action=sults_create_post&security=' + sultsStructureParams.nonce,
            success: function(res) {
                if (res.success) {
                    $btn.text('Redirecionando...');
                    window.location.href = res.data.redirect_url;
                } else {
                    alert('Erro: ' + res.data);
                    $btn.prop('disabled', false).text('Criar Página');
                }
            },
            error: function() {
                alert('Erro de conexão.');
                $btn.prop('disabled', false).text('Criar Página');
            }
        });
    });

    const fields = {
        title: $('#drawer-title'),
        id: $('#drawer-id'),
        status: $('#drawer-status'),
        authorName: $('#drawer-author-name'),
        authorAvatar: $('#drawer-author-avatar'),
        date: $('#drawer-date'),
        category: $('#drawer-category'),
        path: $('#drawer-path'),

        seoTitle: $('#drawer-seo-title'),
        seoDesc: $('#drawer-seo-desc'),
        
        btnEdit: $('#drawer-btn-edit'),
        btnView: $('#drawer-btn-view'),
        
        // Quick Edit Fields
        quickEditId: $('#quick-edit-id'),
        quickEditTitle: $('#quick-edit-title'),
        quickEditSlug: $('#quick-edit-slug'),
        quickEditStatus: $('#quick-edit-status'),
        quickEditCategory: $('#quick-edit-category'),
        quickEditAuthor: $('#quick-edit-author'),
        quickEditSidebar: $('#quick-edit-sidebar'),
        quickEditParent: $('#quick-edit-parent'),
        quickEditPassword: $('#quick-edit-password'),
        quickEditDate: $('#quick-edit-date'),
        quickEditForm: $('#sults-quick-edit-form'),
        quickEditSection: $('.sults-quick-edit-section')
    };

    /* =========================================
       1. LÓGICA DO DRAWER (ABRIR/FECHAR)
       ========================================= */

    wrapper.on('click', '.sults-card-title', function(e) {

        if ($(this).closest('.sults-card').hasClass('disabled')) {
            return;
        }

        e.preventDefault();
        const cardItem = $(this).closest('li.sults-item');
        const postId = cardItem.data('id');

        openDrawer(postId);
    });

    $('.sults-drawer-close, #sults-drawer-backdrop').on('click', function(e) {
        e.preventDefault();
        closeDrawer();
    });

    $(document).on('keyup', function(e) {
        if (e.key === "Escape") closeDrawer();
    });

    function openDrawer(postId) {
        $backdrop.addClass('open');
        $drawer.addClass('open');
        
        $contentState.hide();
        $loadingState.show();
        
        fetchPostDetails(postId);
    }

    function closeDrawer() {
        $backdrop.removeClass('open');
        $drawer.removeClass('open');
    }

    /* =========================================
       2. AJAX E PREENCHIMENTO DE DADOS
       ========================================= */

function fetchPostDetails(postId) {
        $.ajax({
            url: sultsStructureParams.ajax_url,
            type: 'POST',
            data: {
                action: 'sults_get_post_details', 
                security: sultsStructureParams.nonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    populateDrawer(response.data);
                } else {
                    alert('Erro ao carregar detalhes: ' + (response.data || 'Erro desconhecido'));
                    closeDrawer();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error, xhr.responseText);
                alert('Erro de conexão. Verifique o console para mais detalhes.');
                closeDrawer();
            }
        });
    }

    function populateDrawer(data) {
        fields.title.text(data.title);
        fields.id.text('ID: #' + data.id);
        fields.status.html(data.status_html);
        
        fields.authorName.text(data.author.name);
        fields.authorAvatar.attr('src', data.author.avatar);

        fields.date.text(data.date);
        fields.path.text(data.path);

        const catHtml = `<span class="sults-cat-dot" style="background-color: ${data.category.color}"></span> ${data.category.name}`;
        fields.category.html(catHtml);

        fields.seoTitle.text(data.seo.title);
        fields.seoDesc.text(data.seo.description);

        fields.btnView.attr('href', data.links.view);
        
        if (data.links.can_edit) {
            fields.btnEdit.attr('href', data.links.edit).removeClass('disabled').show();
            fields.quickEditSection.show();
            
            // Populate Quick Edit
            fields.quickEditId.val(data.id);
            fields.quickEditTitle.val(data.title);
            fields.quickEditSlug.val(data.slug);
            fields.quickEditStatus.val(data.status);
            fields.quickEditCategory.val(data.category.id);
            fields.quickEditAuthor.val(data.author.id);
            fields.quickEditSidebar.val(data.sidebar_id);
            fields.quickEditParent.val(data.parent_id);
            fields.quickEditPassword.val(data.password);
            fields.quickEditDate.val(data.date);
        } else {
            fields.btnEdit.attr('href', '#').addClass('disabled').hide();
            fields.quickEditSection.hide();
        }

        $loadingState.fadeOut(200, function() {
            $contentState.fadeIn(200);
        });
    }
    
    /* =========================================
       3. INTERATIVIDADE DA ÁRVORE (LEGADO)
       ========================================= */

    wrapper.on('click', '.sults-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const icon = $(this);
        const li = icon.closest('li.sults-item');
        li.toggleClass('sults-closed');
        
        if (li.hasClass('sults-closed')) {
            icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
        } else {
            icon.removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
        }
    });

    wrapper.on('click', '.sults-category-header', function(e) {
        e.preventDefault();
        const folder = $(this).closest('.sults-category-folder');
        const icon = $(this).find('.sults-cat-toggle');
        
        folder.toggleClass('sults-cat-closed');
        
        if (folder.hasClass('sults-cat-closed')) {
            icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
        } else {
            icon.removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
        }
    });

    /* =========================================
       4. DRAG AND DROP (SORTABLE)
       ========================================= */
    
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
            start: function(e, ui) {
                ui.item.find('.sults-card-title').css('pointer-events', 'none');
            },
            stop: function(event, ui) {
                ui.item.find('.sults-card-title').css('pointer-events', ''); 

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
                if(!res.success) alert('Erro ao salvar: ' + (res.data || 'Erro desconhecido'));
            }
        });
    }

    /* =========================================
       5. SALVAR EDIÇÃO RÁPIDA
       ========================================= */
    fields.quickEditForm.on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btn-save-quick-edit');
        const formData = $(this).serialize();
        
        $btn.prop('disabled', true).text('Salvando...');

        $.ajax({
            url: sultsStructureParams.ajax_url,
            type: 'POST',
            data: formData + '&action=sults_save_quick_edit&security=' + sultsStructureParams.nonce,
            success: function(res) {
                if (res.success) {
                    $btn.text('Salvo!');
                    setTimeout(() => {
                        $btn.prop('disabled', false).text('Salvar Alterações');
                        location.reload(); 
                    }, 1000);
                } else {
                    alert('Erro: ' + res.data);
                    $btn.prop('disabled', false).text('Salvar Alterações');
                }
            },
            error: function() {
                alert('Erro de conexão.');
                $btn.prop('disabled', false).text('Salvar Alterações');
            }
        });
    });
});