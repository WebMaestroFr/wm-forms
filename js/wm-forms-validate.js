jQuery(document).ready(function ($) {
  // http://jqueryvalidation.org/validate
  var validator = $('.wm-form').validate({
    debug: true,
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
          submit.hide();
          $('.failure-message, .success-message').remove();
        },
        complete: function () {
          spinner.hide();
          submit.fadeIn('fast');
        },
        success: function (data) {
          console.log(data);
          if (data.hasOwnProperty('errors')) {
            validator.showErrors(data.errors);
          } else if (data.hasOwnProperty('failure')) {
            $('<p>').insertBefore(submit).addClass('failure').text(data.failure);
          } else if (data.hasOwnProperty('success')) {
            $('<p>').insertBefore(submit).addClass('success').text(data.success);
            // $(form)[0].reset();
          } else if (data.hasOwnProperty('redirect')) {
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
