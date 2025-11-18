(function($){
    $('#ldg-submit').on('click', function(e){
        e.preventDefault();
        var $btn = $(this);
        $btn.prop('disabled', true).text('Creating demo...');
        var $form = $('#ldg-demo-form');
        var data = $form.serializeArray();
        // append nonce
        data.push({name: '_wpnonce', value: LDG_DEMO.nonce});
        $.post(LDG_DEMO.ajax_url, $.param(data), function(res){
            if (res.success) {
                $('#ldg-result').html('<div class="notice notice-success"><p>'+res.data.message+'</p></div>');
            } else {
                var msg = res.data && res.data.message ? res.data.message : 'Error creating demo.';
                $('#ldg-result').html('<div class="notice notice-error"><p>'+msg+'</p></div>');
            }
            $btn.prop('disabled', false).text('Create Demo');
        }, 'json').fail(function(xhr){
            var txt = 'Request failed: ' + xhr.status;
            $('#ldg-result').html('<div class="notice notice-error"><p>'+txt+'</p></div>');
            $btn.prop('disabled', false).text('Create Demo');
        });
    });
})(jQuery);