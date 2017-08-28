$(document).ready(function() {

    jQuery.extend( jQuery.fn.dataTableExt.oSort, {
        "numeric-parse-pre": function ( a ) {
            var r = /\d+/;
            return (parseInt(a.match(r)));
        },
        "numeric-parse-asc": function ( a, b ) {
            return ((a < b) ? -1 : ((a > b) ? 1 : 0));
        },
        "numeric-parse-desc": function ( a, b ) {
            return ((a < b) ? 1 : ((a > b) ? -1 : 0));
        }
    } );

    $('.DataTable').DataTable({
        columnDefs: [
          { type: 'numeric-parse', targets: [1, 2] },
          { orderable: false, targets: -1 }
        ]
    });

    $('.DataTable-staff').DataTable({
        order: [],
        columnDefs: [
          { orderable: false, targets: 0 }
        ]
    });

} );