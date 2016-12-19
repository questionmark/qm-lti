$(document).ready(function() {
    $('.DataTable').DataTable( {
    columnDefs: [
      { orderable: false, targets: -1 }
  	]
    });
} );