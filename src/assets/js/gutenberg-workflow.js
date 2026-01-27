(function($) {
    $(document).ready(function() {

        /**
         * Retorna a configuração completa para o status atual
         */
        function obterConfigStatus() {
            var statusAtual = wp.data.select('core/editor').getEditedPostAttribute('status');
            var params = typeof sultsWorkflowParams !== 'undefined' ? sultsWorkflowParams : {};
            var map = params.statusMap || {};

            if (map && map[statusAtual]) {
                return map[statusAtual];
            }
            return null;
        }

        /**
         * Ação: Muda status (opcional) e Salva
         */
        function executarAcao(novoStatus) {
            if (novoStatus) {
                console.log('Sults Workflow: Alterando status para ' + novoStatus + ' e salvando...');
                wp.data.dispatch('core/editor').editPost({ status: novoStatus });
            } else {
                console.log('Sults Workflow: Salvando no status atual...');
            }

            // Pequeno delay para garantir que o estado do React atualizou antes do save
            setTimeout(function() {
                wp.data.dispatch('core/editor').savePost();
            }, 100);
        }

        /**
         * Substitui um botão específico com base no tipo (Primário ou Secundário)
         */
        function gerenciarBotao(seletor, tipoBotao) {
            var $botaoOriginal = $(seletor);

            if (!$botaoOriginal.length) return;

            if (wp.data.select('core/editor').isSavingPost()) return;

            var configStatus = obterConfigStatus();
            var config = null;

            if (configStatus) {
                config = (tipoBotao === 'primary') ? configStatus.primary : configStatus.secondary;
            }

            var textoBotao = config ? config.text : (tipoBotao === 'primary' ? 'Salvar/Publicar' : 'Salvar');
            var proximoStatus = config ? config.target_status : null;

            var classeClone = 'sults-botao-' + tipoBotao;
            var $nossoBotao = $('.' + classeClone);

            if (!$nossoBotao.length) {
                var $clone = $botaoOriginal.clone();

                $clone.addClass(classeClone)
                      .addClass('sults-workflow-btn')
                      .removeClass('editor-post-save-draft')
                      .removeClass('editor-post-publish-button__button')
                      .removeAttr('disabled')
                      .css({
                          'z-index': 10,
                          'position': 'relative',
                          'margin-left': '8px',
                          'width': 'auto',
                          'min-width': '100px'
                      })
                      .text(textoBotao);

                $botaoOriginal.after($clone);
                $botaoOriginal.hide();

                $clone.on('click', function(e) {
                    e.preventDefault();
                    if (proximoStatus) {
                        executarAcao(proximoStatus);
                    } else {
                        $botaoOriginal.trigger('click');
                    }
                });

            } else {
                $botaoOriginal.hide();

                if ($nossoBotao.text() !== textoBotao) {
                    $nossoBotao.text(textoBotao);
                }

                $nossoBotao.off('click').on('click', function(e) {
                    e.preventDefault();
                    if (proximoStatus) {
                        executarAcao(proximoStatus);
                    } else {
                        $botaoOriginal.trigger('click');
                    }
                });
            }
        }

        setInterval(function() {

            gerenciarBotao('button.editor-post-save-draft', 'secondary');

            gerenciarBotao('.editor-post-publish-button__button', 'primary');
            
        }, 500);
    });

})(jQuery);