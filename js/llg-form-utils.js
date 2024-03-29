'use strict'

var jq = jQuery;

jq(document).ready(function(){

  /* Expand text area while focused */
  jq("textarea").focusin (function(){
    jq(this).prop("rows", 10);
  });

  jq("textarea").focusout(function(){
    var texta = jq(this);
    if (texta.val().length == 0) {
      texta.prop("rows", 2);
    }
  });

  jq("#llg-send-form-btn").click(function(e){
    e.preventDefault();

    /* Validate the form */
    if (!validate_form(jq("#llg-event-form"))){
      alert("Please make sure all the required fields are filled in.");
      return;
    }

    var jsObj = serialiseForm(jq("#llg-event-form"));
    submit_form(JSON.stringify(jsObj));
  });

  if (jq("#llg-https-redirect-notice").length > 0 &&
    window.location.protocol.indexOf("https") == -1){
      redirect_to_https();
  }

  jq(".llg-toggler").click(function(e){
    var toToggle = jq(this).data('toggle-selector');
    jq(toToggle).slideToggle();
  });

});

function redirect_to_https(){
  jq("#llg-https-redirect-notice").text("Redirecting to secure server...");
  window.location.protocol = "https:";
}

function submit_form(formJson){

  var postData = 'llg_post_action=save_booking';
  postData += '&_wpnonce='+llgCSRF;
  postData += '&event_id='+llgEventId;
  postData += '&form_data='+formJson;

  jq("#llg-spinner").show();
  jq.post("./",
    postData,
    function(retData) {
      jq("#llg-spinner").hide();
      jq("#llg-booking-area").hide();
      jq("#llg-thank-you").show();
      jq("#booking-ref").text(retData);
      try {
        jq("#llg-thank-you").get(0).scrollIntoView();
      } catch (e) {}
    }
  ).fail(function(retData) {
    jq("#llg-spinner").hide();
    console.warn("Server responded with: "+retData.getResponseHeader("x-llg-booking"));
    alert("Sorry error submitting, did you provide the correct anti-spam answer? If so please Contact Us");
  });
}

function validate_form(formToValidate){
	var missing_values = [];

	formToValidate.find(":required").not(":hidden").each(function(){

		var type = jq(this).prop("type");

		if (type == "radio" || type == "checkbox"){

			var inputName = jq(this).prop("name");

			if (formToValidate.find("[name="+inputName+"]:checked").length == 0){

        var reqCon = jq(this).parents(".llg-options-container-required");
          reqCon.addClass("llg-missing-value");
          reqCon.one('click', function(){
            reqCon.removeClass("llg-missing-value");
          });

        missing_values.push(jq(this));
			}

		} else {

			if(!jq(this).val()){
				missing_values.push(jq(this));

				jq(this).one('keyup', function(){
					jq(this).removeClass("llg-missing-value");
				});

				jq(this).addClass("llg-missing-value");
			}

		}
	});

  return (missing_values.length === 0)
}

/* I don't want W3C standard serialised form as I need "no value"
 * to be present for fields in the form such as checkboxes which
 * wouldn't otherwise be included by .serializeArray etc
 */

function serialiseForm(formToSerialise){

  var formObj = {};

  formToSerialise.find("[name]").not("[type=hidden]").each(function(){
    var type = jq(this).prop("type");
    var val = "";
    var key = jq(this).prop("name");

    if (type == "radio" || type == "checkbox"){

      formToSerialise.find("[name="+key+"]:checked").each(function(){
        val += jq(this).val() + ' ';
      });

      val = val.trim();

    } else {
      val = jq(this).val();
      /* Make sure we don't overwrite nested keys whilst
       * maintaining a flat strucutre
       */
      var j = 0;
      while (formObj[key] != undefined){
        j++;
        key = key+'-'+j.toString();
      }

    }

    if (!val){
      val = "";
    }

    formObj[key] = val;

  });
  return formObj;
}
