jQuery(document).ready(function ($) {
    'use strict';
    var results = $('.wm-form-results-selector');
    $('select', results).change(function() {
      results.submit();
    });
});
