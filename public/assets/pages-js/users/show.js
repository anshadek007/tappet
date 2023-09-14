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
        order: [0, 'ASC'],
        pageLength: 10,
        "columns": [
            {"data": "name"},
            {"data": "name_ar"},
            {"data": "cat_name"},
            {"data": "price"},
            {"data": "price_type"},
            {"data": "condition"}
        ],
        columnDefs: [
            {
                targets: [0],
                searchable: true,
                sortable: true
            },
            {
                targets: [1],
                searchable: true,
                sortable: true,
                visible:false
            },
            {
                targets: [2],
                searchable: true,
                sortable: true
            },
            {
                targets: [3],
                searchable: true,
                sortable: true
            },
            {
                targets: [4],
                searchable: true,
                sortable: true
            },
            {
                targets: [5],
                searchable: true,
                sortable: true
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
            "url": controller_url + '/list-advertisement-data',
            "type": "POST",
            "async": false,
            "data": {'_token': token, 'name': name, 'user_id':user_id},
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
    get_all_data();

    jQuery('.search_filter').click(function () {
        jQuery(data_table).dataTable().fnDestroy();
        get_all_data();
    });

    jQuery('.reset_filter').click(function () {
        $('#name').val('');
        setTimeout(function () {
            jQuery(data_table).dataTable().fnDestroy();
            get_all_data();
        }, 100);
    });
});