"use strict";
var UserRolesCustomJsBlocks = function() {

    var initUserRoleTable = function() {

        var table = $('#user_role_table');

        table.DataTable({
            responsive: true,
            dom: `<'row'<'col-sm-12'tr>>
			<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>`,
            lengthMenu: [5, 10, 25, 50],
            pageLength: 10,
            order: [[0, 'asc']],
            columnDefs: [],
        });

    };

    var initUserRoleUserTable = function() {

        var table = $('#role_user_list_table');

        table.DataTable({
            responsive: true,
            dom: `<'row'<'col-sm-12'tr>>
			<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>`,
            lengthMenu: [5, 10, 25, 50],
            pageLength: 10,
            order: [[0, 'asc']],
            columnDefs: [],
        });

    };

    var initUserRolePermissionTable = function() {

        var table = $('#role_permission_list_table');

        table.DataTable({
            responsive: true,
            dom: `<'row'<'col-sm-12'tr>>
			<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>`,
            lengthMenu: [5, 10, 25, 50],
            pageLength: 10,
            order: [[0, 'asc']],
            columnDefs: [],
        });

    };

    var initUserRolePermissionMapTable = function() {

        var table = $('#role_permission_map_table');

        table.DataTable({
            responsive: true,
            dom: `<'row'<'col-sm-12'tr>>
			<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>`,
            lengthMenu: [5, 10, 25, 50],
            pageLength: 5,
            order: [[0, 'asc']],
            columnDefs: [],
        });

        jQuery('#user_role_edit_form').on('submit', function(e) {
            var form = this;
            // Iterate over all checkboxes in the table
            table.$('select').each(function(){
                // If checkbox doesn't exist in DOM
                if(!$.contains(document, this)){
                    // If checkbox is checked
                    $(form).append(
                        $('<input>')
                            .attr('type', 'hidden')
                            .attr('name', this.name)
                            .val(this.value)
                    );
                }
            });
        });

    };

    return {
        listPage: function() {
            initUserRoleTable();
        },
        viewPage: function() {
            initUserRoleUserTable();
            initUserRolePermissionTable();
        },
        newPage: function(hostUrl) {
            jQuery('button#new_user_role_cancel_btn').on('click', function(e) {
                window.location = hostUrl + '/userrole/roles';
            });
        },
        editPage: function(hostUrl) {
            initUserRolePermissionMapTable();
            jQuery('button#edit_user_role_cancel_btn').on('click', function(e) {
                window.location = hostUrl + '/userrole/roles';
            });
        }
    };

}();
