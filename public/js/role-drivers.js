"use strict";
var RoleDriversCustomJsBlocks = function() {

    var initRoleDriversListTable = function() {
        var table = $('#role_driver_list_table');
        var dataTable = table.DataTable({
            responsive: true,
            dom: `<'row'<'col-sm-12'tr>>
			<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>`,
            lengthMenu: [5, 10, 25, 50],
            pageLength: 5,
            order: [[0, 'asc']],
            columnDefs: []
        });
    };

    var initRoleDriverOrderListTable = function() {
        var table = $('#driver_view_orders_table');
        var dataTable = table.DataTable({
            responsive: true,
            dom: `<'row'<'col-sm-12'tr>>
			<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>`,
            lengthMenu: [5, 10, 25, 50],
            pageLength: 5,
            order: [[0, 'asc']],
            columnDefs: []
        });
    };

    return {
        listPage: function(hostUrl){
            initRoleDriversListTable();
        },
        viewPage: function(hostUrl) {
            initRoleDriverOrderListTable();
        },
    };

}();
