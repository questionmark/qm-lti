$(document).ready( function() {

  onLoad();

});

function onLoad() {
  $('#id_save').disabled = true;
  $('#id_launch').disabled = ($('#id_url').val().length <= 0) || ($('#id_key').val().length <= 0);
}

function onChange() {
  $('#id_save').disabled = false;
  $('#id_launch').disabled = true;
}

function doLaunch() {
  location.href = 'test_launch.php';
}

function doReset() {
  if (confirm('Reset.  Are you sure?')) {
    location.href = 'test_reset.php';
  }
}
