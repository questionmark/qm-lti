$(document).ready( function() {

  var is_saved = false;
  window.onload = function() {
    window.addEventListener("beforeunload", function (e) {
      console.log(is_saved);
      if (is_saved || $('#id_save').is(':disabled')) {
          return undefined;
      }
      var confirmationMessage = 'Are you sure you want to leave? Unsaved changes will not be saved.';
      (e || window.event).returnValue = confirmationMessage;
      return confirmationMessage;
    });
  };

  $( '#id_save' ).click( function(e) {
    is_saved = true;
  });
});

function doChange(id) {
  doReset();
  if ($("#" + id).length != 0) {
    $("#" + id).removeClass('show').addClass('show');
    $('#' + id).removeClass('hide');
  }
  $('#id_save').prop( "disabled", false );
}

function doReset() {
  $('#id_save').prop( "disabled", true );
  for (var i=1; i<=document.forms[0].assessment.length; i++) {
    if ($('#img' + i).length != 0) {
      $('#img' + i).removeClass('hide').addClass('hide');
      $('#img' + i).removeClass('show');
    }

  }
}
