"use strict";
var UsersCustomJsBlocks = function() {

    var initUserListTable = function() {

        var table = $('#user_list_table');

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

    var showHidePasswordFn = function(targetElementSelector) {
        var targetElement = $(targetElementSelector);
        targetElement.find('span.show-hide-password').on('click', function(event) {
            event.preventDefault();
            if(targetElement.find('input').attr("type") === "text"){
                targetElement.find('input').attr('type', 'password');
                targetElement.find('span.show-hide-password i').addClass( "fa-eye-slash" );
                targetElement.find('span.show-hide-password i').removeClass( "fa-eye" );
            }else if(targetElement.find('input').attr("type") === "password"){
                targetElement.find('input').attr('type', 'text');
                targetElement.find('span.show-hide-password i').removeClass( "fa-eye-slash" );
                targetElement.find('span.show-hide-password i').addClass( "fa-eye" );
            }
        });
    };

    return {
        listPage: function() {
            initUserListTable();
        },
        newPage: function(hostUrl) {
            showHidePasswordFn('#user_password_form_group');
            showHidePasswordFn('#user_password_conf_form_group');
            let dpImage = new KTImageInput('profile_avatar_area');
            jQuery('button#new_user_cancel_btn').on('click', function(e) {
                window.location = hostUrl + '/userauth/users';
            });
        },
        editPage: function(hostUrl) {
            let dpImage = new KTImageInput('profile_avatar_area');
            jQuery('button#edit_user_cancel_btn').on('click', function(e) {
                window.location = hostUrl + '/userauth/users';
            });
        },
        editProfilePage: function(hostUrl) {
            let dpImage = new KTImageInput('profile_avatar_area');
        },
        passwordChangePage: function(hostUrl) {
            showHidePasswordFn('#user_password_form_group');
            showHidePasswordFn('#new_password_form_group');
            showHidePasswordFn('#new_password_conf_form_group');
        }
    };

}();


