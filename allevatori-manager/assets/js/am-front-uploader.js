jQuery(function($){
    console.log("Uploader inizializzato");

    $('.am-upload-btn').on('click', function(e){
        e.preventDefault();
        console.log("Upload button cliccato");

        let type = $(this).data('type');
        let target = type === 'featured' ? '#immagine_evidenza' : '#logo_allevamento';
        let preview = type === 'featured' ? '#immagine_evidenza_preview' : '#logo_allevamento_preview';

        if (!$(target).length || !$(preview).length) {
            console.error("Target o preview non definiti");
            return;
        }

        let input = $('<input type="file" accept="image/*">');
        input.on('change', function(){
            if(!this.files.length) return;
            console.log("File selezionato:", this.files[0]);

            let fd = new FormData();
            fd.append('action', 'am_upload_allevatore_image');
            fd.append('file', this.files[0]);
            fd.append('type', type);
            fd.append('security', amAllevatore.nonce);

            console.log("Avvio upload file...", this.files[0]);

            $.ajax({
                url: amAllevatore.ajax_url,
                type: 'POST',
                data: fd,
                contentType: false,
                processData: false,
                success: function(res){
                    console.log("Risposta AJAX:", res);
                    if(res.success){
                        // SALVA l'ID dell'immagine e NON l'URL
                        $(target).val(res.data.id);
                        $(preview).attr('src', res.data.url).show();
                        console.log("Upload completato con successo");
                    } else {
                        alert('Errore upload: '+res.data);
                    }
                },
                error: function(xhr, status, error){
                    console.error("Errore di comunicazione", status, error);
                    alert("Errore di comunicazione");
                }
            });
        });

        input.trigger('click');
    });
});
