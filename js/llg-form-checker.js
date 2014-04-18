
jQuery (document).ready (function () {
/* Expand text area while focused */
 jQuery ("textarea").focusin (function () {
  jQuery (this).prop ("rows", 10);
 });

  jQuery ("textarea").focusout (function () {
    var texta = jQuery (this);
    if (texta.val ().length == 0) {
     texta.prop ("rows", 2);
    }
  });
});

function set_border_red (item, set)
{
  if (set)
    item.css ('border', '1px solid red');
  else
    item.css ('border', '');
}

/* form is the jQuery object for the form */
function llg_check_form (form)
{
var failed = false;
form.find('input').not (".not-required").each (function () {
  var item = jQuery (this);
  if (!item.val ()) {
    failed = true;

    set_border_red (item, true);

    item.keypress (function () {
      /* Reset the border on the item */
      set_border_red (item, false);
    });
  }

  if (item.attr ('type') == 'radio') {
    /*Get the group name and make sure one item is checked */
    var group_name = item.attr ('name');
    if (!jQuery ("input[name=\""+group_name+"\"]:checked").val ()) {
      failed = true;
      var parent_item = item.parent ();

      set_border_red (parent_item, true);

      item.change (function () {
           /* Reset the border on the item */
          set_border_red (parent_item, false);
      });
    }
  }

});


form.find('textarea').not (".not-required").each (function () {
  if (jQuery (this).val () == '') {
    failed = true;
    var item = jQuery (this);

    set_border_red (item, true);

    item.keypress (function () {
      /* Reset the border on the item */
      set_border_red (item, false);
    });
  }
});




return failed;
}
