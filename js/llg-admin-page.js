/* London Link Group plugin admin functions
 * Author: Michael Wood
 */


var jq = jQuery;

jq(document).ready(function(){

  jq(".toggle-event-details").click(function(e){
    e.preventDefault();
    var llgFormId = jq(this).data('for');
    var llgForm = jq("."+llgFormId);
    /* close already visible */
    jq(".event-details:visible").slideUp();

    if (llgForm.is(":visible")){
      return;
    }
    llgForm.slideToggle();
  });

  jq(".update-event-btn").click(function(e){
    var eventId = jq(this).data('event-id');
    var form = jq("#llg-update-event-form-"+eventId);

    if (check_inputs_val(form.find("input[type=text]"))){
      return;
    } else {
      form.submit();
    }
  });

  jq(".delete-event").click(function(){
    var id = jq(this).data('event-id');

    var deleteForm = jq("#"+id+"-form-delete");
    var eventNameToDelete = jq("#"+id+"-event-name").val();

    var ret = confirm("Really delete event \'"+eventNameToDelete+"\' and all its data?!");
    if (ret == true) {
      deleteForm.submit ();
    }
  });

  jq("#add-new-event").click(function(){
    var form = jq("#llg-new-event-form");

    if(check_inputs_val(form.find("input[type=text]"))){
      return;
    } else {
      form.submit();
    } 
  });


  jq("#llg-bad-pass-msg a").click(function(e){
    e.preventDefault();

    jq(this).parent().fadeOut();

    window.history.pushState(null, null,
       window.location.search.replace('bad_pass=1',''));

  });

  jq("input[type=password]").focus(function(){
    jq("#llg-bad-pass-msg:visible a").click();
  });

}); /* End on ready */




function check_inputs_val(inputs){
  var errors = inputs.length;

  if (errors === 0){
    console.warn("err No inputs given to validate");
    return 1;
  }

  inputs.each(function(i){
    if (jq(this).val().length > 0){
      errors--;
    } else {
      jq(this).css("border", "1px solid red");
    }
  });

  if (errors != 0){
    alert("Please complete all required fields ("+errors+" missing)");
  }

  return errors;
}


