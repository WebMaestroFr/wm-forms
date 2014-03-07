jQuery(document).ready(function ($) {
    'use strict';
    if (!$('body').hasClass('post-type-wm_form')) { return; }
    var box = $('#wm-form-fields'),
      results = $('.wm-form-results');
    if ( box.length === 1 ) {
      var input = $('.wm-form-fields-json', box),
        fids = $('.wm-form-fields-increment', box),
        template = _.template($('.wm-form-field-template', box).html()),
        optTemplate = _.template('<li><input type="text" class="widefat" value="<%= opt %>" /></li>'),
        list = $('.wm-form-fields-list', box),
        toggleOptions = function (item, type) {
            $('.wm-form-options', item).toggle(type === 'select' || type === 'radio');
        },
        addFieldItem = function (data) {
            var item = $(template(data)),
                i;
            list.append(item);
            for (i = 0; i < data.options.length; i += 1) {
                $('ol', item).append($(optTemplate({ opt: data.options[i] })));
            }
            toggleOptions(item, data.type);
            $('.wm-form-field-type').change(function () {
                toggleOptions(item, $(this).val());
            });
            $('.item-edit', item).click(function (e) {
                e.preventDefault();
                item.toggleClass('item-active').find('dd').slideToggle('fast');
            });
            $('.wm-form-add-option', item).click(function (e) {
                e.preventDefault();
                $('ol', item).append($(optTemplate({ opt: '' })));
            });
            $('.item-delete', item).click(function (e) {
                e.preventDefault();
                item.slideUp('fast', function () {
                    item.remove();
                });
            });
        },
        fields = $.parseJSON(input.val()),
        i;
      if (fields !== null) {
        for (i = 0; i < fields.length; i += 1) {
            addFieldItem(fields[i]);
        }
      }
      $('.wm-form-add-field', box).click(function (e) {
        var fid = parseInt(fids.val());
        fids.val(fid + 1);
        e.preventDefault();
        addFieldItem({
            fid: fid,
            label: 'Label',
            type: 'text',
            required: 0,
            options: []
        });
        $('.item-edit', box).last().trigger('click');
      });
      list.sortable();
      $('#post').submit(function (e) {
        fields = [];
        $('dl', box).each(function (i, el) {
            var type = $('.wm-form-field-type', el).val(),
                field = {
                    fid: $('.wm-form-field-id', el).val(),
                    label: $('.wm-form-field-label', el).val(),
                    type: type,
                    required: $('.wm-form-field-required', el).is(':checked'),
                    options: []
                };
            if (type === 'select' || type === 'radio') {
                $('.wm-form-options ol input', el).each(function (j, opt) {
                    field.options.push($(opt).val());
                });
            }
            fields.push(JSON.stringify(field));
        });
        input.val('[' + fields.join() + ']');
      });
    }
    if ( results.length > 0 ) {
      $('select', results).change(function() {
        results.submit();
      });
    }
});
