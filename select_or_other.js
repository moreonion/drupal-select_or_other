// $Id$

var other_shown = 0;

function select_or_other_check_and_show(uniqid, speed) {
  var other_selected = 0;
  $("span#"+uniqid+" select.select-or-other option:selected").each(function () {
    if ($(this).val() == 'select_or_other') {
      other_selected = 1;
    }
  });
  if (other_selected == 1 && other_shown == 0) {
    $("span#"+uniqid+" input.select-or-other").show(speed);
    other_shown = 1;
  }
  else if (other_selected == 0 && other_shown == 1) {
    $("span#"+uniqid+" input.select-or-other").hide(speed);
    other_shown = 0;
  }
}

$(document).ready(function() {
  $("input.select-or-other").each(function () {
    $(this).hide(0);
    select_or_other_check_and_show($(this).parents("span.select-or-other").attr("id"), 0);
  });
  $("select.select-or-other").change(function () { 
    select_or_other_check_and_show($(this).parents("span.select-or-other").attr("id"), 'normal');
  });
});