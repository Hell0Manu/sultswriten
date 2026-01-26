(function(wp, $) {
    const { registerPlugin } = wp.plugins;
    const { PluginPostStatusInfo } = wp.editPost; 
    const { select, dispatch } = wp.data;
    const { createElement, useState, useEffect } = wp.element;
    const { Button, Spinner } = wp.components;

    const WorkflowPanel = () => {
        const [transitions, setTransitions] = useState([]);
        const [loading, setLoading] = useState(true);
        const [processing, setProcessing] = useState(false);
        
        const postId = select('core/editor').getCurrentPostId();
        const postStatus = select('core/editor').getEditedPostAttribute('status');

        useEffect(() => {
            if (postId) {
                fetchTransitions();
            }
        }, [postId, postStatus]);

        const fetchTransitions = () => {
            $.ajax({
                url: sultsWorkflowParams.ajax_url,
                type: 'POST',
                data: {
                    action: 'sults_get_post_details', 
                    security: sultsWorkflowParams.nonce,
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

        const handleTransition = (slug) => {
             if (!confirm('Tem certeza que deseja mudar o status?')) return;
             
             setProcessing(true);
             
             dispatch('core/editor').savePost().then(() => {
                 $.ajax({
                    url: sultsWorkflowParams.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'sults_update_status',
                        security: sultsWorkflowParams.nonce,
                        post_id: postId,
                        new_status: slug
                    },
                    success: (res) => {
                        if(res.success) {
                             window.location.reload();
                        } else {
                            alert('Erro: ' + res.data);
                            setProcessing(false);
                        }
                    },
                    error: () => {
                        alert('Erro de conexão.');
                        setProcessing(false);
                    }
                });
             });
        };

        if (loading) return createElement('div', {style: {padding:'10px'}}, 'Carregando fluxo...');
        
        if (!transitions || transitions.length === 0) return null;

        return createElement('div', { className: 'sults-gutenberg-workflow' },
            createElement('h4', null, 'Próxima Etapa'),
            processing ? createElement(Spinner) : 
            transitions.map(t => 
                createElement(Button, {
                    key: t.slug,
                    isPrimary: true,
                    className: `sults-gutenberg-btn sults-status-${t.slug}`, 
                    onClick: () => handleTransition(t.slug)
                }, t.label)
            )
        );
    }

    registerPlugin('sults-workflow-panel', {
        render: () => createElement(PluginPostStatusInfo, null, createElement(WorkflowPanel))
    });

})(window.wp, jQuery);