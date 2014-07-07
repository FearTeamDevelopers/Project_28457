jQuery.noConflict();
jQuery(document).ready(function() {
    
    jQuery(window).load(function() {
        jQuery("#loader, .loader").hide();
    });
    
    jQuery('div.wrapper').hide();
    jQuery('div.wrapper').fadeIn(1200);

});