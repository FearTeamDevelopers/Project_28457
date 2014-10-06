jQuery.noConflict();

jQuery(document).ready(function() {

    jQuery('form').submit(function() {
        jQuery('#loader').show();
    });

    jQuery(window).load(function() {
        jQuery("#loader, .loader").hide();
        
        jQuery.post('/system/showprofiler', function(msg){
            jQuery('body').append(msg);
        });
    });
    
    jQuery('a.view').lightBox();
    
    /* GLOBAL SCRIPTS */

    jQuery('#top-nav li').hover(
            function() {
                jQuery('ul', this).slideDown(100);
            },
            function() {
                jQuery('ul', this).slideUp(100);
            }); /*for the dropdown sub-nav*/

    jQuery('.ad-notif-success, .ad-notif-warn, .ad-notif-error, .ad-notif-info').click(function() {
        jQuery(this).parent('div.container_12').hide(500);
    }); /* for dismissing notifications */

    jQuery('.box-head').click(function() {
        jQuery(this).next(".box-content").css("min-height", "0").slideToggle(400);
    }); /* for collapsing panels on header click */

    /* FOR ADAPTIVE LAYOUT, remove if not needed */

    function fullHeight() {
        if (jQuery(window).width() > 860) {
            if (jQuery(window).width() < 1250) {
                jQuery(".top-bar").height(jQuery(document).height());
            }
            else {
                jQuery(".top-bar").height(80);
            }
        }
        else {
            jQuery(".top-bar").height(42);
        }
    }
    fullHeight();

    jQuery(window).resize(function() {
        fullHeight();
    });
    /* sets the .top-bar height to 100% of document height if resolution smaller than 1250px and larger than 600px */

    jQuery('.imagelist a.delete').click(function(event) {
        event.preventDefault();
        var parent = jQuery(this).parents('li');
        var c = confirm('Delete this file?');
        
        if (c) {
            var url = jQuery(this).attr('href');
            var tk = jQuery('#tk').val();

            jQuery.post(url, {tk: tk}, function(msg) {
                if (msg == 'success') {
                    parent.hide('explode', 500);
                } else {
                   jQuery('#dialog').html(msg);
                }
            });
        }
        return false;
    });

    //activate/deactivate image in grid list
    jQuery('.imagelist a.activate').click(function(event) {
        event.preventDefault();
        var parent = jQuery(this).parents('li');
        var url = jQuery(this).attr('href');

        jQuery.post(url, function(msg) {
            if (msg == 'active') {
                parent.removeClass('photoinactive').addClass('photoactive');
            } else if (msg == 'inactive') {
                parent.removeClass('photoactive').addClass('photoinactive');
            } else {
                alert(msg);
            }
        });

        return false;
    });

    //delete individual row
    jQuery('.stdtable a.button-delete-ajax, .mediatable a.button-delete-ajax').click(function() {
        var c = confirm('Continue delete?');
        var parentTr = jQuery(this).parents('tr');

        if (c) {
            var tk = jQuery('#tk').val();
            var url = jQuery(this).attr('href');

            jQuery.post(url, {tk: tk}, function(msg) {
                if (msg == 'success') {
                    parentTr.fadeOut();
                } else {
                    alert(msg);
                }
            });
        }
        return false;
    });
    
    jQuery('.stdtable .ajax-button').click(function() {
        var c = confirm('Do you want to continue?');
        var parentTr = jQuery(this).parents('tr');

        if (c) {
            var tk = jQuery('#tk').val();
            var url = jQuery(this).attr('href');

            jQuery.post(url, {tk: tk}, function(msg) {
                if (msg == 'success') {
                    parentTr.fadeOut();
                } else {
                    alert(msg);
                }
            });
        }
        return false;
    });

    jQuery('.display-no-header').dataTable({
        "aaSorting": [],
        "bJQueryUI": true,
        "iDisplayLength": 25,
        "bLengthChange": false,
        "sPaginationType": "full_numbers"
    });

    jQuery('.display-short').dataTable({
        "aaSorting": [],
        "bJQueryUI": true,
        "iDisplayLength": 10,
        "sPaginationType": "full_numbers"
    });

    jQuery('.display').dataTable({
        "aaSorting": [],
        "bJQueryUI": true,
        "iDisplayLength": 25,
        "sPaginationType": "full_numbers"
    });

    jQuery('.display-extended').dataTable({
        "aaSorting": [],
        "bJQueryUI": true,
        "iDisplayLength": 50,
        "sPaginationType": "full_numbers"
    });

    jQuery("#datepicker, #datepicker2").datepicker({
        defaultDate: "+1w",
        changeMonth: true,
        firstDay: 1,
        changeYear: true,
        dateFormat: "yy-mm-dd",
        numberOfMonths: 1
    });

    jQuery(".button-task").button({
        icons: {
            primary: "ui-icon-plus"
        },
        text: false
    });
    jQuery(".button-detail").button({
        icons: {
            primary: "ui-icon-search"
        },
        text: false
    });
    jQuery(".button-copy").button({
        icons: {
            primary: "ui-icon-copy"
        },
        text: false
    });
    jQuery(".button-edit, .controll-button-edit").button({
        icons: {
            primary: "ui-icon-pencil"
        },
        text: false
    });
    jQuery(".button-delete, .controll-button-delete, .button-delete-ajax").button({
        icons: {
            primary: "ui-icon-trash"
        },
        text: false
    });
    jQuery(".button-download").button({
        icons: {
            primary: "ui-icon-document"
        },
        text: false
    });

    jQuery(".tab-nav").tabs();

    jQuery("#report-bug").click(function() {
        jQuery("#dialog").load('/setting/reportBug').dialog({
            title: "Report Bug",
            width: "550px",
            modal: true,
            position: {my: "center", at: "top", of: window},
            buttons: {
                Cancel: function() {
                    jQuery(this).dialog("close");
                }
            }
        });
    });

    jQuery("button.ajax-dialog, a.ajax-dialog").click(function() {
        var href = jQuery(this).attr('href');
        var val = jQuery(this).attr('value');

        jQuery('#dialog p').load(href);

        jQuery('#dialog').dialog({
            title: val,
            width: 600,
            modal: true,
            position: {my: 'center', at: 'top', of: window},
            buttons: {
                Close: function() {
                    jQuery(this).dialog('close');
                }
            }
        });
        return false;
    });

    jQuery(function() {
        var progress = jQuery("#progressVal").val() * 100;
        jQuery(".progressbar").progressbar({
            value: progress
        });
    });

    jQuery('.stdtable .checkall').click(function() {
        var parentTable = jQuery(this).parents('table');
        var ch = parentTable.find('tbody input[type=checkbox]');
        if (jQuery(this).is(':checked')) {

            //check all rows in table
            ch.each(function() {
                jQuery(this).attr('checked', true);
                jQuery(this).parents('tr').addClass('selected');
            });

            //check both table header and footer
            parentTable.find('.checkall').each(function() {
                jQuery(this).attr('checked', true);
            });

        } else {
            //uncheck all rows in table
            ch.each(function() {
                jQuery(this).attr('checked', false);
                jQuery(this).parents('tr').removeClass('selected');
            });

            //uncheck both table header and footer
            parentTable.find('.checkall').each(function() {
                jQuery(this).attr('checked', false);
            });
        }
    });

    //for checkbox
    jQuery('input[type=checkbox]').each(function() {
        var t = jQuery(this);
        t.click(function() {
            if (jQuery(this).is(':checked')) {
                t.attr('checked', true);
                t.parents('tr').addClass('selected');
            } else {
                t.attr('checked', false);
                t.parents('tr').removeClass('selected');
            }
        });

        if (jQuery(this).is(':checked')) {
            t.attr('checked', true);
            t.parents('tr').addClass('selected');
        } else {
            t.attr('checked', false);
            t.parents('tr').removeClass('selected');
        }
    });



    //check if there is/are selected row in table
    jQuery('.massActionForm').submit(function() {
        var sel = false;
        var ch = jQuery(this).find('tbody input[type=checkbox]');

        ch.each(function() {
            if (jQuery(this).is(':checked')) {
                sel = true;
            }
        });

        if (!sel) {
            alert('No data selected');
            return false;
        } else {
            return true;
        }
    });
    
    //multi file upload
    jQuery('.uploadFileForm .multi_upload').click(function() {
        if (jQuery('.uploadFileForm .file_inputs input[type=file]').length < 7) {
            jQuery('.uploadFileForm .file_inputs input[type=file]')
                    .last()
                    .after('<input type="file" name="files[]" />');
        }
    });

    jQuery('.uploadFileForm .multi_upload_dec').click(function() {
        if (jQuery('.uploadFileForm .file_inputs input[type=file]').length > 1) {
            jQuery('.uploadFileForm .file_inputs input[type=file]').last().remove();
        }
    });

    jQuery('.uploadFileForm').submit(function() {
        jQuery('#loader').show();
    });
});

CKEDITOR.replace('ckeditor');
CKEDITOR.replace('ckeditor2');