jQuery(document).ready(function ($) {
  'use strict';
  var select = $('#forms-select'),
    table = $('#form-results');
  $('select', select).change(function() {
    select.submit();
  });
  $('.select-all', table).change(function () {
    var checked = $(this).is(':checked');
    console.log(checked);
    $('.select-result', table).attr('checked', checked);
  });
  table.tablesorter();
});
