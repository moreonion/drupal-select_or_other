/**
 * @file select_or_other.js
 */

(function ($) {

  // Make .val() work on our .select-or-other wrappers.
  var original = null;
  if (typeof $.valHooks === 'undefined') {
    $.valHooks = {};
  }
  if (typeof $.valHooks !== 'undefined' && typeof $.valHooks.div !== 'undefined') {
    original = $.valHooks.div;
  }
  $.valHooks.div = {
    get: function (elem) {
      var obj = $(elem).data('selectOrOther');
      if (obj) {
        return obj.get();
      }
      if (original && original.get) {
        return original.get(elem);
      }
    },
    set: function (elem, value) {
      var obj = $(elem).data('selectOrOther');
      if (obj) {
        return obj.set(value);
      }
      if (original && original.set) {
        return original.set(elem, value);
      }
    }
  };

  function bind($wrapper) {
    var hide_other = $wrapper.data('selectOrOtherHide');
    hide_other = hide_other && hide_other !== '0' && hide_other !== 'false' || hide_other == undefined;
    var multiple = $wrapper.is('.select-or-other-multiple');
    var $other_element = $wrapper.find('.select-or-other-other').closest('.form-item');
    var $other_input = $other_element.find('input');
    var $select_element = $wrapper.find('.select-or-other-select');
    var $other_option = $select_element.find('[value=select_or_other]');
    var prop = $select_element.is('select') ? 'selected' : 'checked';

    var other_selected = function() {
      return $other_option.is(':selected, :checked');
    };

    var triggerUpdate = function (event) {
      var data = {'userInput': typeof event != 'undefined'};
      $wrapper.triggerHandler('select-or-other-update', data);
    };
    var updateRequired = function () {
      $other_input.prop('required', other_selected());
    };
    var updateVisibility = function (event, data) {
      var speed = data.userInput ? 200 : 0;
      if (other_selected()) {
        $other_element.show(speed, function() {
          if (data.userInput) {
            $other_input.focus();
          }
        });
      }
      else {
        $other_element.hide(speed);
      }
    };
    $select_element.not('select').click(triggerUpdate);
    $select_element.change(triggerUpdate);
    $wrapper.on('select-or-other-update', updateRequired);
    if (hide_other) {
      $wrapper.on('select-or-other-update', updateVisibility);
    }
    else {
      $other_input.on('click', function () {
        $other_option.prop(prop, true).trigger('change');
      });
    }
    $wrapper.bind('change', function(event, values) {
      // Replace change events in the select_or_other with change events on the wrapper.
      if ($wrapper.is(event.target)) {
        return;
      }
      event.stopPropagation();
      $wrapper.trigger('change');
    });

    var get_value = multiple ? function() {
      var selected = [];
      $select_element.find('select :selected, :checked').not($other_option).each(function () {
        selected.push($(this).val());
      });
      if (other_selected()) {
        selected.push($other_input.val());
      }
      return selected;
    } : function () {
      return other_selected() ? $other_input.val() : $select_element.find('select, :checked').val();
    };
    $wrapper.data('selectOrOther', {
      get: get_value,
      set: function(values) {
        if (typeof values == 'string') {
          values = [values];
        }
        $select_element.find('option, input').prop(prop, false);
        values.forEach(function (value) {
          var $e = $select_element.find('[value="' + value + '"]');
          if (!$e.length) {
            $e = $other_option;
            $other_input.val(value);
          }
          $e.prop(prop, true);
        });
        triggerUpdate();
      }
    });
    // Initial update of the elements.
    triggerUpdate();
  }

  /**
   * The Drupal behaviors for the Select (or other) field.
   */
  Drupal.behaviors.select_or_other = {
    attach: function(context) {
      $(".select-or-other:not('.select-or-other-processed')", context)
        .addClass('select-or-other-processed')
        .each(function () {
          bind($(this));
        });
    }
  };

})(jQuery);
