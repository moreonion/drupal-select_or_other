// $Id$

function select_or_other_check_and_show(ele, page_init) {
  var speed;
  if (page_init) {
    speed = 0;
  } else {
    speed = 400;
    ele = $(ele).parents(".select-or-other")[0];
  }
  if ($(ele).find(".select-or-other-select option:selected[value=select_or_other], .select-or-other-select:checked[value=select_or_other]").length) {
    $(ele).find(".select-or-other-other").show(speed);
  }
  else {
    $(ele).find(".select-or-other-other").hide(speed);
  }
}

/**
 * The Drupal behaviors for the Select (or other) field.
 */
Drupal.behaviors.select_or_other = function(context) {
  $(".select-or-other:not('.select-or-other-processed')", context)
    .addClass('select-or-other-processed').each(function () {
    select_or_other_check_and_show(this, true);
  });
  $(".select-or-other-select:not('.select-or-other-processed')", context)
    .addClass('select-or-other-processed').click(function () {
    select_or_other_check_and_show(this, false);
  });
};

