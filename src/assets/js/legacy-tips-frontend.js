(function($) {
    'use strict';

    $(function() {
        $('.wp-block-table').each(function() {
            var $table = $(this);
            var text = $table.text();

            if (text.indexOf('Dica SULTS') !== -1) {
                
                var cleanText = text.replace(/Dica SULTS:?/gi, '').trim();
                
                var iconUrl = window.sultsWritenSettings 
                    ? window.sultsWritenSettings.tipsIconUrl 
                    : SULTSWRITEN_TIPS_ICON;

                
                var $aside = $('<aside>', { class: 'dica-sults' });
                
                var $img = $('<img>', { 
                    src: iconUrl, 
                    alt: 'Dica SULTS' 
                });

                var $div = $('<div>');
                var $h3  = $('<h3>').text('Dica SULTS');
                var $sults_p   = $('<p>').text(cleanText);

                $div.append($h3).append($sults_p);
                $aside.append($img).append($div);
                $table.replaceWith($aside);
            }
        });
    });

})(jQuery);