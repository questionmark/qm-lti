window.onload = function() {
  window.addEventListener("beforeunload", function (e) {
    if ($('#id_save').is(':disabled')) {
        return undefined;
    }
    var confirmationMessage = 'Are you sure you want to leave? Unsaved changes will not be saved.';
    (e || window.event).returnValue = confirmationMessage;
    return confirmationMessage;
  });

};

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
