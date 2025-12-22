(function (wp, lodash) {
    'use strict';

    var hooks = wp.hooks;
    var blocks = wp.blocks;
    var domReady = wp.domReady;

    function renameVerseToDica(settings, name) {
        if (name === 'core/verse') {
            return lodash.assign({}, settings, {
                title: 'Dica SULTS',
                icon: 'lightbulb', 
                description: 'Insira um bloco de destaque ou dica.'
            });
        }
        
        return settings;
    }

    hooks.addFilter(
        'blocks.registerBlockType',
        'sults-writen/rename-blocks',
        renameVerseToDica
    );

    domReady(function () {
        
        const blocksAllowed = [
            'core/paragraph',      
            'core/heading',        
            'core/list',           
            'core/list-item',      
            'core/pullquote',          
            'core/table',                   
            'core/details',
            'core/verse',
            // 'sults-writen/dica',
            
            'core/file',
            'core/image',           
            'core/separator',      
            'core/spacer',        
          
            'core/columns',
            'core/column' 
        ];

        var allBlocks = blocks.getBlockTypes().map(function(block) {
            return block.name;
        });

        allBlocks.forEach(function(blockName) {
            if (blocksAllowed.indexOf(blockName) === -1) {
                blocks.unregisterBlockType(blockName);
            }
        });

    });

})(window.wp, window.lodash);