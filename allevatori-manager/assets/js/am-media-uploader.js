jQuery(document).ready(function($){

    // Funzione riutilizzabile per ogni campo immagine
    function initMediaUploader(buttonId, inputId, previewId) {

        $(document).on("click", buttonId, function(e){
            e.preventDefault();

            // Crea frame multimediale
            var frame = wp.media({
                title: 'Seleziona immagine',
                button: { text: 'Usa questa immagine' },
                multiple: false
            });

            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();

                // Inserisce ID nell'input
                $(inputId).val(attachment.id);

                // Aggiorna preview
                $(previewId)
                    .attr('src', attachment.url)
                    .css('display', 'block');
            });

            frame.open();
        });
    }

    // Inizializza i due uploader
    initMediaUploader(
        '#upload_immagine_evidenza',
        '#immagine_evidenza',
        '#immagine_evidenza_preview'
    );

    initMediaUploader(
        '#upload_logo_allevamento',
        '#logo_allevamento',
        '#logo_allevamento_preview'
    );
});
