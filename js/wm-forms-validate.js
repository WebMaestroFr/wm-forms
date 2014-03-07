jQuery(document).ready(function ($) {
  $('form').each(function () {
    $(this).validate({
      submitHandler: function(form) {
        $.ajax({
          url: ajaxUrl,
          type: 'POST',
          data: $.extend({ action: 'wm_form' }, $(form).serializeObject()),
          success: function (data) {
            console.log( data );
          }
        });
      }
    });
  });
});
