/**
 * @file select_or_other.js
 */

(function ($) {

  function bind($wrapper) {
    var $other_element = $wrapper.find('.select-or-other-other').closest('.form-item');
    var $other_input = $other_element.find('input');
    var $select_element = $wrapper.find('.select-or-other-select');
    var $other_option = $select_element.find('[value=select_or_other]');
    var speed = 200;
    var toggle_required = $.fn.prop ? function (required) {
      $other_input.prop('required', required);
    } : function(required) {
      return required ? $other_input.attr('required', true) : $other_input.removeAttr('required');
    }

    var other_selected = function() {
      return $other_option.is(':selected, :checked');
    };

    if (other_selected()) {
      toggle_required(true);
    }
    else {
      $other_element.hide();
      // Special case, when the page is loaded, also apply 'display: none' in case it is
      // nested inside an element also hidden by jquery - such as a collapsed fieldset.
      $other_element.css('display', 'none');
    }

    var update = function () {
      if (other_selected()) {
        toggle_required(true);
        $other_element.show(speed, function() {
          $other_element.find('.select-or-other-other').focus();
        });
      }
      else {
        $other_element.hide(speed);
        toggle_required(false);
      }
    }
    $select_element.not('select').click(update);
    $select_element.filter('select').change(update);
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
