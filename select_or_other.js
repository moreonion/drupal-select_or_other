/**
 * @file select_or_other.js
 */

(function ($) {

  // Helper functions for jQuery < 1.5 compatibilty.
  if ($.fn.prop) {
    var setProp = function($e, prop) {
      $e.prop(prop, true);
    };
    var removeProp = function($e, prop) {
      $e.prop(prop, false);
    }
  }
  else {
    var setProp = function($e, prop) {
      $e.attr(prop, prop);
    };
    var removeProp = function($e, prop) {
      $e.removeAttr(prop);
    }
  }

  function bind($wrapper) {
    var multiple = $wrapper.is('.select-or-other-multiple');
    var $other_element = $wrapper.find('.select-or-other-other').closest('.form-item');
    var $other_input = $other_element.find('input');
    var $select_element = $wrapper.find('.select-or-other-select');
    var $other_option = $select_element.find('[value=select_or_other]');
    var speed = 200;

    var other_selected = function() {
      return $other_option.is(':selected, :checked');
    };

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

    if (other_selected()) {
      setProp($other_input, 'required');
    }
    else {
      $other_element.hide();
      // Special case, when the page is loaded, also apply 'display: none' in case it is
      // nested inside an element also hidden by jquery - such as a collapsed fieldset.
      $other_element.css('display', 'none');
    }

    var update = function () {
      if (other_selected()) {
        setProp($other_input, 'required');
        $other_element.show(speed, function() {
          $other_element.find('.select-or-other-other').focus();
        });
      }
      else {
        $other_element.hide(speed);
        removeProp($other_input, 'required');
      }
    }
    $select_element.not('select').click(update);
    $select_element.filter('select').change(update);
    $wrapper.bind('change', function(event, values) {
      $wrapper.trigger('select-or-other-change', {
        'multiple': multiple,
        'value': get_value(),
      });
    });

    $wrapper.bind('select-or-other-set', function(event, values) {
      if (typeof values == 'string') {
        values = [values];
      }
      var prop = $select_element.is('select') ? 'selected' : 'checked';
      removeProp($select_element.find('option, input'), prop);
      values.forEach(function (value) {
        var $e = $select_element.find('[value="' + value + '"]');
        if (!$e.length) {
          $e = $other_option;
          $other_input.val(value);
        }
        setProp($e, prop);
      });
      update();
    });
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
