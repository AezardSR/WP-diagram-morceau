jQuery(document).ready(function($){
    $('#upload_diagram_button').click(function(e) {
        e.preventDefault();
        var image = wp.media({
            title: 'Choisir un Diagramme',
            multiple: false
        }).open()
        .on('select', function(e){
            var uploaded_image = image.state().get('selection').first();
            var image_url = uploaded_image.toJSON().url;
            $('#diagramme').val(image_url);
        });
    });
});
