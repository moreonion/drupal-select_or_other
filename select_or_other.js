// $Id$

function select_or_other_check_and_show(ele, page_init) {
  var speed;
  if (page_init) {
    speed = 0;
  } else {
    speed = "normal";
    ele = $(ele).parents("div.select-or-other")[0];
  }

  if ($(ele).find("select.select-or-other-select option:selected[value=select_or_other], input.select-or-other-select:checked[value=select_or_other]").length) {
    $(ele).find("input.select-or-other-other").show(speed);
  }
  else {
    $(ele).find("input.select-or-other-other").hide(speed);
  }
}

$(document).ready(function () {
  $("div.select-or-other").each(function () { 
    select_or_other_check_and_show(this, true);
  });

  $("input.select-or-other-select").click(function () { 
    select_or_other_check_and_show(this, false);
  });
  $("select.select-or-other-select").change(function () { 
    select_or_other_check_and_show(this, false);
  });
});