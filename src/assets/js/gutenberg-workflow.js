(function(wp, $) {
    const { registerPlugin } = wp.plugins;
    const { PluginPrePublishPanel } = wp.editPost; 
    const { select, dispatch, useSelect } = wp.data;
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
        
        const { lockPostSaving, unlockPostSaving } = dispatch('core/editor');
        
        const isSidebarEnabled = useSelect( ( select ) => select( 'core/editor' ).isPublishSidebarEnabled(), [] );
        const postId = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostId(), [] );

        useEffect(() => {
            if ( isSidebarEnabled ) {
                lockPostSaving( 'sults-workflow-lock' );
            } else {
                unlockPostSaving( 'sults-workflow-lock' );
            }
            
            return () => unlockPostSaving( 'sults-workflow-lock' );
        }, [ isSidebarEnabled ] );

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
             
             unlockPostSaving( 'sults-workflow-lock' );
             
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
                            lockPostSaving( 'sults-workflow-lock' );
                        }
                    },
                    error: () => {
                        setProcessing(false);
                        lockPostSaving( 'sults-workflow-lock' );
                    }
                });
             }).catch((err) => {
                 console.error(err);
                 setProcessing(false);
                 lockPostSaving( 'sults-workflow-lock' );
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
    // 2. O HACK VISUAL (JQUERY) 
    // =========================================================================
    $(document).ready(function() {
        
        function mascararBotaoPublicar() {
            var $btn = $('.editor-post-publish-button__button'); 
            
            if ($btn.length && $btn.text() !== 'Salvar') {
                
                $btn.addClass('sults-workflow-main-btn');
                
                $btn.text('Salvar'); 
                
            }
        }


        function forcarPrePublishCheck() {
            const isPrePublishEnabled = wp.data.select('core/editor').isPublishSidebarEnabled();
            if (!isPrePublishEnabled && !wp.data.select('core/editor').isSavingPost()) {

            }
        }
        setInterval(function() {
            mascararBotaoPublicar();
        }, 500);

    });

})(window.wp, jQuery);