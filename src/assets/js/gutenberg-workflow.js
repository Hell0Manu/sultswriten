(function(wp, $) {
    const { registerPlugin } = wp.plugins;
    const { PluginPrePublishPanel } = wp.editPost; 
    const { select, dispatch } = wp.data;
    const { createElement, useState, useEffect } = wp.element;
    const { Button, Spinner, RadioControl } = wp.components;

    const params = typeof sultsWorkflowParams !== 'undefined' ? sultsWorkflowParams : { statusMap: {} };

    // =========================================================================
    // 1. O CONTEÚDO DO MODAL (PRE-PUBLISH PANEL)
    // =========================================================================
    const WorkflowPanelContent = () => {
        const [transitions, setTransitions] = useState([]);
        const [loading, setLoading] = useState(true);
        const [selectedOption, setSelectedOption] = useState('');
        const [processing, setProcessing] = useState(false);
        
        const postId = select('core/editor').getCurrentPostId();

        useEffect(() => {
            if (postId) fetchTransitions();
        }, [postId]);

        const fetchTransitions = () => {
            $.ajax({
                url: params.ajax_url,
                type: 'POST',
                data: {
                    action: 'sults_get_post_details', 
                    security: params.nonce,
                    post_id: postId
                },
                success: (res) => {
                    if(res.success && res.data.transitions) {
                        setTransitions(res.data.transitions);
                    }
                    setLoading(false);
                },
                error: () => setLoading(false)
            });
        };

        const handleConfirm = () => {
             if(!selectedOption) return;
             
             setProcessing(true);
             
             dispatch('core/editor').savePost().then(() => {
                 $.ajax({
                    url: params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'sults_update_status',
                        security: params.nonce,
                        post_id: postId,
                        new_status: selectedOption
                    },
                    success: (res) => {
                        if(res.success) window.location.reload();
                        else {
                            alert('Erro: ' + res.data);
                            setProcessing(false);
                        }
                    },
                    error: () => setProcessing(false)
                });
             });
        };

        if (loading) return createElement('p', null, 'Carregando opções...');
        if (!transitions.length) return createElement('p', null, 'Nenhuma ação disponível.');

        const options = transitions.map(t => ({ label: t.label, value: t.slug }));

        return createElement(
            PluginPrePublishPanel,
            {
                className: 'sults-workflow-panel',
                title: 'Definir Próxima Etapa', 
                initialOpen: true
            },
            createElement('div', { className: 'sults-workflow-content' },
                createElement('p', { style: { marginBottom: '15px' } }, 'Selecione para onde este post deve ir agora:'),
                
                createElement(RadioControl, {
                    selected: selectedOption,
                    options: options,
                    onChange: (val) => setSelectedOption(val)
                }),

                createElement(Button, {
                    isPrimary: true,
                    isBusy: processing,
                    disabled: !selectedOption || processing,
                    style: { marginTop: '20px', width: '100%', justifyContent: 'center' },
                    onClick: handleConfirm
                }, 'Confirmar e Mover')
            )
        );
    };

    registerPlugin('sults-workflow-prepublish', {
        render: WorkflowPanelContent,
        icon: 'flag'
    });


    // =========================================================================
    // 2. O HACK VISUAL (JQUERY) - Igual ao PublishPress
    // =========================================================================
    $(document).ready(function() {
        
        function mascararBotaoPublicar() {
            var $btn = $('.editor-post-publish-button__button'); 
            
            if ($btn.length && $btn.text() !== 'Workflow') {
                
                $btn.addClass('sults-workflow-main-btn');
                

                $btn.text('Workflow'); 
                
                if ($btn.attr('aria-disabled') === 'true' && !wp.data.select('core/editor').isSavingPost()) {
                    $btn.attr('aria-disabled', 'false');
                }
            }
        }


        function forcarPrePublishCheck() {
            const isPrePublishEnabled = wp.data.select('core/editor').isPublishSidebarEnabled();
            if (!isPrePublishEnabled) {
                wp.data.dispatch('core/editor').enablePublishSidebar();
            }
        }

        function moverPainelParaTopo() {
            var $meuPainel = $('.sults-workflow-panel');
            var $container = $('.editor-post-publish-panel__prepublish');


            if ($meuPainel.length && $container.length && !$container.children().first().is($meuPainel)) {
                $container.prepend($meuPainel); 
            }
        }

        setInterval(function() {
            mascararBotaoPublicar();
            forcarPrePublishCheck();
        }, 500);

    });

})(window.wp, jQuery);