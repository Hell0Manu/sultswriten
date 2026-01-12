(function (blocks, element, blockEditor) {
    var el = element.createElement;
    var registerBlockType = blocks.registerBlockType;
    var RichText = blockEditor.RichText;
    var useBlockProps = blockEditor.useBlockProps;

    var iconUrl = window.sultsWritenSettings ? window.sultsWritenSettings.tipsIconUrl : '';

    registerBlockType('sults-writen/dica', {
        title: 'Dica SULTS',
        icon: 'lightbulb', 
        category: 'text',
        keywords: ['dica', 'sults', 'aviso', 'box'],
        attributes: {
            content: {
                type: 'string',
                source: 'html',
                selector: 'p',
            },
        },

        edit: function (props) {
            var content = props.attributes.content;
            var blockProps = useBlockProps({ className: 'dica-sults-editor' });

            function onChangeContent(newContent) {
                props.setAttributes({ content: newContent });
            }

            return el(
                'div',
                blockProps,
                el(
                    'div',
                    { className: 'dica-sults' }, 
                    [
                        el('img', {
                            src: iconUrl,
                            alt: 'Ícone Dica',
                            style: { width: '60px', flexShrink: 0 }
                        }),
                        el('div', {}, [
                            el('h3', {}, 'Dica SULTS'),
                            el(RichText, {
                                tagName: 'p',
                                className: 'dica-content',
                                onChange: onChangeContent,
                                value: content,
                                placeholder: 'Escreva a dica aqui...',
                                keepPlaceholderOnFocus: true
                            })
                        ])
                    ]
                )
            );
        },

        save: function (props) {
            var blockProps = useBlockProps.save({ className: 'dica-sults' }); 

            return el(
                'aside',
                blockProps,
                [
                    el('img', {
                        src: iconUrl,
                        alt: 'Dica SULTS'
                    }),
                    el('div', {}, [
                        el('h3', {}, 'Dica SULTS'),
                        el(RichText.Content, {
                            tagName: 'p',
                            value: props.attributes.content
                        })
                    ])
                ]
            );
        }
    });
})(window.wp.blocks, window.wp.element, window.wp.blockEditor);