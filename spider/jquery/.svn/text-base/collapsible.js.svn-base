(function($) {
    function hideFieldset(obj, options) {
        if (options.animate) {
            obj.find('div.fieldset-wrapper').slideUp(options.speed);
        } else {
            obj.find('div.fieldset-wrapper').hide();
        }
        obj.addClass("collapsed");
    }
    
    function showFieldset(obj, options) {
        if (options.animate) {
            obj.find('div.fieldset-wrapper').slideDown(options.speed);
        } else {
            obj.find('div.fieldset-wrapper').show();
        }
        obj.removeClass("collapsed");
    }
    
    function toggleFieldset(obj, options) {
        if (obj.hasClass('collapsed')) {
            showFieldset(obj, options);
        } else {
            hideFieldset(obj, options);
        }
    }
    
    $.fn.collapsible = function(options) {
        var setting = {
            animate:true,
            speed:'fast'
        };
        $.extend(setting,options);
        this.each(function() {
            var fieldset=$(this);
            var legend=fieldset.children('legend');
            
            if (fieldset.hasClass('collapsed')) {
                hideFieldset(fieldset, {
                    animate:false
                });
            }
            legend.click(function() {
                //alert(setting);
                toggleFieldset(fieldset, setting);
            });
        });
    }
    
})(jQuery);