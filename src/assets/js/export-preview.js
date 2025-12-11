(function($) {
    'use strict';

    $(document).ready(function() {
        
        var editorLeftSetting = wp.codeEditor.initialize('code-left', {
            codemirror: { 
                readOnly: true, 
                lineNumbers: true, 
                mode: 'htmlmixed',
                lineWrapping: true 
            }
        });
        
        var editorRightSetting = wp.codeEditor.initialize('code-right', {
            codemirror: { 
                readOnly: true, 
                lineNumbers: true, 
                mode: 'htmlmixed',
                lineWrapping: true 
            }
        });

        var cmLeft = editorLeftSetting.codemirror;
        var cmRight = editorRightSetting.codemirror;

        function formatXml(xml) {
            if (!xml) return '';
            var formatted = '';
            var reg = /(>)(<)(\/*)/g;
            xml = xml.replace(reg, '$1\r\n$2$3');
            var pad = 0;
            
            jQuery.each(xml.split('\r\n'), function(index, node) {
                var indent = 0;
                if (node.match( /.+<\/[\w:\%\@\-]+[^>]*>$/ )) {
                    indent = 0;
                } else if (node.match( /^<\/[\w:\%\@\-]+/ )) {
                    if (pad != 0) { pad -= 1; }
                } else if (node.match( /^<[\w:\%\@\-]+[^>]*[^\/|^%]>$|^<[\w:\%\@\-]+[^>]*[^%]>$/ )) {
                    if ( !node.match( /^<(img|br|hr|input|meta|link)/ ) ) {
                        indent = 1;
                    }
                } else {
                    indent = 0;
                }

                var padding = '';
                for (var i = 0; i < pad; i++) { padding += '  '; }

                formatted += padding + node + '\r\n';
                pad += indent;
            });
            return formatted;
        }

        var contentRaw = $('#data-raw').val();
        var contentClean = $('#data-clean').val();
        var contentJsp = $('#data-jsp').val();

        cmLeft.setValue(formatXml(contentClean)); 
        cmRight.setValue(formatXml(contentJsp));

        $('.CodeMirror').css('height', '100%');

        $('.sults-copy-btn').on('click', function(e) {
            e.preventDefault();
            var btn = $(this);
            var target = btn.data('target');
            var contentToCopy = (target === 'left') ? cmLeft.getValue() : cmRight.getValue();

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(contentToCopy).then(function() {
                    btn.addClass('copied');
                    btn.find('.btn-text').text('Copiado!');
                    btn.find('.dashicons').attr('class', 'dashicons dashicons-yes');
                    setTimeout(function() {
                        btn.removeClass('copied');
                        btn.find('.btn-text').text('Copiar');
                        btn.find('.dashicons').attr('class', 'dashicons dashicons-clipboard');
                    }, 2000);
                });
            } else {
                var $temp = $("<textarea>");
                $("body").append($temp);
                $temp.val(contentToCopy).select();
                document.execCommand("copy");
                $temp.remove();
            }
        });

        $('.sults-view-toggle').on('click', function(e) {
            e.preventDefault();
            $('.sults-view-toggle').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            var mode = $(this).data('mode');

            if (mode === 'cleaning') {
                // Modo: HTML Puro -> Limpo
                $('#title-left').text('HTML Original (Sujo)');
                $('#title-right').text('HTML Processado (Limpo)');
                
                cmLeft.setValue(formatXml(contentRaw));
                cmRight.setValue(formatXml(contentClean));
                cmRight.setOption('mode', 'htmlmixed');

            } else if (mode === 'conversion') {
                // Modo: HTML Limpo -> JSP
                $('#title-left').text('HTML Processado (Limpo)');
                $('#title-right').text('Código JSP (Final)');
                
                cmLeft.setValue(formatXml(contentClean));
                cmRight.setValue(formatXml(contentJsp)); 
            }
        });

    });
})(jQuery);