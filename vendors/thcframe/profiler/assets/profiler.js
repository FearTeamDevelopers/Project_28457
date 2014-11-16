jQuery.noConflict();
jQuery(document).ready(function () {
    jQuery(".profiler-show-query").click(function () {
        var key = jQuery(this).attr('value');
        jQuery('#'+key+'_db').toggle();
    });
    jQuery(".profiler-show-globalvar").click(function () {
        var key = jQuery(this).attr('value');
        jQuery('#'+key+'_vars').toggle();
    });
    jQuery(".profiler-query tr td.backtrace").click(function () {
        var a = jQuery(this).css("height");
        var b = a.replace("px", "");
        if (b >= 250) {
            jQuery(this).parent("tr").css("height", "40px");
        } else {
            jQuery(this).parent("tr").css("height", "300px");
        }
        jQuery(this).children("div").toggle();
    });
});