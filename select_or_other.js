// $Id$

function select_or_other_check_and_show(uniqid, speed) {
  var other_selected = false;
  $("div#"+uniqid+" select.select-or-other-select option:selected, div#"+uniqid+" input.select-or-other-select:checked").each(function () {
    if ($(this).val() === 'select_or_other') {
      other_selected = true;
    }
  });
  if (other_selected) {
    $("div#"+uniqid+" input.select-or-other-other").show(speed);
  }
  else {
    $("div#"+uniqid+" input.select-or-other-other").hide(speed);
  }
}

$(document).ready(function() {
  $("input.select-or-other-other").each(function () {
    $(this).hide(0);
    select_or_other_check_and_show($(this).parents("div.select-or-other").attr("id"), 0);
  });
  $(".select-or-other-select").change(function () { 
    select_or_other_check_and_show($(this).parents("div.select-or-other").attr("id"), 'normal');
  });
});