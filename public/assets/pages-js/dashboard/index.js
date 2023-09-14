"use strict";

var data_table = '#datatable';
function get_all_data() {
    var name = $('#name').val();
    var token = jQuery("#csrf-token").prop("content");
    jQuery(data_table).dataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        pagingType: "full_numbers",
        scrollY: '50vh',
        scrollCollapse: true,
        searching: false,
        order: [0, 'DESC'],
        pageLength: 10,
        "columns": [
            {"data": "name"},
            {"data": "name_ar"},
            {"data": "sender_name"},
            {"data": "message"},
            {"data": "action"}
        ],
        columnDefs: [
            {
                targets: [0],
                searchable: false,
                sortable: false
            },
            {
                targets: [1],
                searchable: false,
                sortable: false,
                visible:false
            },
   
            {
                targets: [2],
                searchable: false,
                sortable: false
            },
            {
                targets: [3],
                searchable: false,
                sortable: false
            },
            {
                targets: [4],
                searchable: false,
                sortable: false
            }
        ],
        language: {
            emptyTable: "No data available",
            zeroRecords: "No matching records found...",
            infoEmpty: "No records available"
        },
        oLanguage: {
            "sProcessing": ''
        },
        ajax: {
            "url": controller_url + '/list-advertisement-reports-data',
            "type": "POST",
            "async": false,
            "data": {'_token': token},
        },
        drawCallback: function () {
            jQuery('<li><a onclick="refresh_tab()" style="cursor:pointer" title="Refresh"><i class="ion-refresh" style="font-size:25px"></i></a></li>').prependTo('div.dataTables_paginate ul.pagination');
        }
    });
}

function refresh_tab() {
    jQuery(data_table).dataTable().fnDestroy();
    get_all_data();
    jQuery("#datatable_list_filter").css('display', 'none');
}

function filterGlobal() {
    jQuery(data_table).dataTable().search(
            jQuery('#global_filter').val()
            ).draw();
}

function filterColumn(i) {
    jQuery(data_table).dataTable().column(i).search(
            jQuery('#col' + i + '_filter').val()
            ).draw();
}

jQuery(document).ready(function () {
    //get_all_data();
    
    // delete data
    jQuery(document).on('click', '.block_data_button', function () {
        var id;
        var this_row = jQuery(this);
        id = jQuery(this).attr('data-id');
        if (!isNaN(id)) {
            swal({
                title: 'Are you sure you want to block this advertisement?',
                text: '',
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(function(willDelete) {
                if (willDelete) {
                    jQuery.ajax({
                        "url": controller_url + '/block-advertisement',
                        type: "POST",
                        data: {
                            'id': id,
                        },
                        dataType: 'json',
                        cache: false,
                        success: function (response) {
                            if (response.success == true) {
                                setTimeout(function () {
                                    swal(response.message, {
                                        icon: 'success',
                                    });
                                    jQuery(data_table).DataTable().row(this_row).remove().draw(false);
                                }, 2000);

                            } else {
                                setTimeout(function () {
                                    swal(response.message, {
                                        icon: 'error',
                                    });
                                }, 2000);
                            }
                        },
                        error: function () {
                            setTimeout(function () {
                                swal("Problem in performing your action.", {
                                    icon: 'info',
                                });
                            }, 2000);
                        }
                    });
                }
            });
        }
    });
});     