jQuery(document).ready(function() {
    var $ = jQuery;
    
    $('.licenses').on('click', '.license-reset', function(e){
        e.preventDefault();
        $.ajax({
            type: "POST",
            url: actlys_licenses_ajax.ajaxurl,
            dataType: 'json',
            data: {
              action: 'actlys_licences_reset',
              nonce: $('#licenses-nonce').val(),
            },
            beforeSend: function(){
              $('.licenses').addClass('--is-loading');
            },
            success: function(data) {
              $('.licenses').removeClass('--is-loading');
              if ( data.error ) {
                alert(data.message);
              } else {
                $('#license-key').val(data.api_key);
              }

              $('.license-copy').trigger('click');

            },
            error: function() {
              $('.licenses').removeClass('--is-loading');
              alert('Something went wrong while sending request. Please try again later.');
            }
        });
    });
    $('.licenses').on('click', '.license-copy', function(e){
        e.preventDefault();
        var inputField = document.getElementById('license-key');
        inputField.select();
        document.execCommand('copy');
	      window.getSelection().removeAllRanges();
        $('.license-input').addClass('--copied');
        setTimeout(function() {
          $('.license-input').removeClass('--copied');
        }, 2500);

    });

});