
function toggleProfileEdit() {
    jQuery('div.contribution-userprofile').toggle();
    jQuery('span.contribution-userprofile-visibility').toggle();
}

function enableContributionAjaxForm(url) {
    jQuery(document).ready(function() {
        // Div that will contain the AJAX'ed form.
        var form = jQuery('#contribution-type-form');

        /* NEW VERSION WITH ONLY ONE ELEMENT TYPE */
        // Elements that should be hidden when there is no type form on the page.
        //var elementsToHide = jQuery('#contribution-confirm-submit, #contribution-contributor-metadata');
        // Duration of hide/show animation.
        //var duration = 0;
        // Remove the noscript-fallback type submit button.
        //jQuery('#submit-type').remove();

        // There should be only one possible value for contribution types
        //var value = '3';

        /*elementsToHide.hide();
        form.hide(duration, function() {
            form.empty();
            jQuery.post(url, {contribution_type: value}, function(data) {
               form.append(data);
               form.show(duration, function() {
                   form.trigger('contribution-form-shown');
                   form.trigger('omeka:tabselected');
                   elementsToHide.show();
                   //in case profile info is also being added, do the js for that form
                   jQuery(form).trigger('omeka:elementformload');
                   jQuery('.contribution-userprofile-visibility').click(toggleProfileEdit);
               });
            });
        });
        */

        // OLD VERSION KEPT AS TEMPLATE
        // Select element that controls the AJAX form. NOTE ID already in use ?
        var contributionType = jQuery('#contribution-type .btn');
        // Elements that should be hidden when there is no type form on the page.
        var elementsToHide = jQuery('#contribution-confirm-submit, #contribution-contributor-metadata');
        // Duration of hide/show animation.
        var duration = 0;

        // Remove the noscript-fallback type submit button.
        jQuery('#submit-type').remove();

        // When the select is changed, AJAX in the type form
        contributionType.on("click", function () {
            var name = this.name;
            contributionType.removeClass("active");
            jQuery(this).addClass("active");
            if (name == "temoignage") {
              value = 3;
            }
            else if (name == "doctech" || name == "docadm" || name == "texteregl" || name == "comm") {
              value = 4;
            }
            elementsToHide.hide();
            form.hide(duration, function() {
                form.empty();
                if (value != "") {
                    jQuery.post(url, {contribution_type: value, doc_type: name}, function(data) {
                       form.append(data);
                       form.show(duration, function() {
                           form.trigger('contribution-form-shown');
                           form.trigger('omeka:tabselected');
                           elementsToHide.show();
                           //in case profile info is also being added, do the js for that form
                           jQuery(form).trigger('omeka:elementformload');
                           jQuery('.contribution-userprofile-visibility').click(toggleProfileEdit);
                       });
                    });
                }
            });
        });
        /*
        contributionType.change(function () {
            var value = this.value;
            console.log(value);
            elementsToHide.hide();
            form.hide(duration, function() {
                form.empty();
                if (value != "") {
                    jQuery.post(url, {contribution_type: value}, function(data) {
                       form.append(data);
                       form.show(duration, function() {
                           form.trigger('contribution-form-shown');
                           form.trigger('omeka:tabselected');
                           elementsToHide.show();
                           //in case profile info is also being added, do the js for that form
                           jQuery(form).trigger('omeka:elementformload');
                           jQuery('.contribution-userprofile-visibility').click(toggleProfileEdit);
                       });
                    });
                }
            });
        });
        */
    });
}

jQuery(document).ready(function() {
    jQuery('.contribution-userprofile-visibility').click(toggleProfileEdit);
    var form = jQuery('#contribution-type-form');
    jQuery(form).trigger('omeka:elementformload');
});
