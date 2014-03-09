jQuery(document).ready(function ($) {
  $('.wm-form').each(function () {
    var validator = $(this).validate({
      errorElement: 'span',
      errorPlacement: function (error, el) {
        el.closest('div, p').append(error);
      },
      submitHandler: function(form) {
        var submit = $('[type="submit"]', form),
          spinner = $('<img>').attr({
            src: ajax.spinner,
            alt: 'loading'
          }).insertAfter(submit).hide();
        $.ajax({
          data: $.extend({ action: 'wm_form' }, $(form).serializeObject()),
          dataType: 'json',
          type: 'POST',
          url: ajax.url,
          beforeSend: function () {
            spinner.fadeIn('fast');
            submit.attr('disabled', 'disabled');
          },
          complete: function () {
            spinner.fadeOut('fast');
            submit.removeAttr('disabled');
          },
          success: function (data) {
            validator.resetForm();
            if (data.hasOwnProperty('errors')) {
              validator.showErrors(data.errors);
            }
            if (data.hasOwnProperty('success')) {
              console.log( data );
            }
            if (data.hasOwnProperty('redirect')) {
              document.location = data.redirect;
            }
          },
          error: function (jqXHR, textStatus, errorThrown) {
            console.log(jqXHR, textStatus, errorThrown);
          }
        });
      }
    });
  });
});
