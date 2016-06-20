if (!Omeka) {
    var Omeka = {};
}

Omeka.Elements = {};

(function ($) {
    /**
     * Send an AJAX request to update a <div class="field"> that contains all
     * the form inputs for an element.
     *
     * @param {jQuery} fieldDiv
     * @param {Object} params Parameters to pass to AJAX URL.
     * @param {string} elementFormPartialUri AJAX URL.
     * @param {string} recordType Current record type.
     * @param {string} recordId Current record ID.
     */
    Omeka.Elements.elementFormRequest = function (fieldDiv, params, elementFormPartialUri, recordType, recordId) {
        var elementId = fieldDiv.attr('id').replace(/element-/, '');

        fieldDiv.find('input, textarea, select').each(function () {
            var element = $(this);
            // Workaround for annoying jQuery treatment of checkboxes.
            if (element.is('[type=checkbox]')) {
                params[this.name] = element.is(':checked') ? '1' : '0';
            } else {
                // Make sure TinyMCE saves to the textarea before we read
                // from it
                if (element.is('textarea')) {
                    var mce = tinyMCE.get(this.id);
                    if (mce) {
                        mce.save();
                    }
                }
                params[this.name] = element.val();
            }
        });

        recordId = typeof recordId !== 'undefined' ? recordId : 0;

        params.element_id = elementId;
        params.record_id = recordId;
        params.record_type = recordType;

        $.ajax({
            url: elementFormPartialUri,
            type: 'POST',
            dataType: 'html',
            data: params,
            success: function (response) {
                fieldDiv.find('textarea').each(function () {
                    tinyMCE.execCommand('mceRemoveControl', false, this.id);
                });
                fieldDiv.html(response);
                fieldDiv.trigger('omeka:elementformload');
            }
        });
    };

    /**
     * Set up add/remove element buttons for ElementText inputs.
     *
     * @param {Element} element The element to search at and below.
     * @param {string} elementFormPartialUrl AJAX URL for form inputs.
     * @param {string} recordType Current record type.
     * @param {string} recordId Current record ID.
     */
    var map = null;
    Omeka.Elements.makeElementControls = function (element, elementFormPartialUrl, recordType, recordId) {
        var addSelector = '.add-element';
        var removeSelector = '.remove-element';
        var fieldSelector = 'div.field';
        var inputBlockSelector = 'div.input-block';
        var context = $(element);
        var fields;

        if (context.is(fieldSelector)) {
            fields = context;
        } else {
            fields = context.find(fieldSelector);
        }

        // Load openlayer div
        loadMapDiv = function(elementId) {
          var locationElement = jQuery(elementId);
          html = '<div id="mapdiv"></div>';
          locationElement.after(html);


          map = new L.map('mapdiv');
          // create the tile layer with correct attribution

          var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
          var osmAttrib='Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
          var osm = new L.TileLayer(osmUrl, {minZoom: 3, maxZoom: 15, attribution: osmAttrib});

          // start the map in South-East England
          map.addLayer(osm);
          map.setView([0, 0], 0, {
            reset: true
          });
          map.invalidateSize();
        }

        // Geocode function
        function geocode(address)
        {
          var openStreetMapGeocoder = GeocoderJS.createGeocoder('openstreetmap');
          openStreetMapGeocoder.geocode(address, function(result) {
            L.marker([result[0]['latitude'], result[0]['longitude']]).addTo(map);
            var latlng = new L.LatLng(result[0]['latitude'], result[0]['longitude']);
            map.setView(latlng, 11, {
              reset: true,
              animate: true
            });
            map.invalidateSize(); // NOTE Mandatory because without this call, tiles are not properly displayed

          });

        }

        // Show remove buttons for fields with 2 or more inputs.
        fields.each(function () {
            var removeButtons = $(this).find(removeSelector);
            if (removeButtons.length > 1) {
                removeButtons.show();
            } else {
                removeButtons.hide();
            }
            // Add form-control class for bootstrap
            // NOTE Had to be done with jQuery because Omeka creates only input elements and no "button" : check FormSubmit.php in Zend/View/Helper
            $(this).find('input, textarea, select').each(function () {
              var element = $(this);
              if ( element.hasClass("add-element") ) {
                element.addClass("btn icon-btn btn-primary");
                var text = element.val();
                var newElem = $('<a></a>', {html: element.html()});
                $.each(this.attributes, function() {
            			newElem.attr(this.name, this.value);
            		});
            		$(this).replaceWith(newElem);
                newElem.text(text);
                newElem.prepend('<span class="glyphicon btn-glyphicon glyphicon-plus img-circle text-primary"></span>');
              }
              else if ( element.hasClass("remove-element") ) {
                element.addClass("btn icon-btn btn-warning");
                var text = element.val();
                var newElem = $('<a></a>', {html: element.html()});
                $.each(this.attributes, function() {
            			newElem.attr(this.name, this.value);
            		});
            		$(this).replaceWith(newElem);
                newElem.text(text);
                newElem.prepend('<span class="glyphicon btn-glyphicon glyphicon-minus img-circle text-warning"></span>');

              }
              // Add geonames API query using jeoquery
              else if (element.attr('id') == "Elements-265-0-text") {
                var jeoelement = $('#Elements-265-0-text');
                jeoelement.addClass("ui-autocomplete-input input-large");
                // Initialization
                // jeoQuery autocomplete
                jeoquery.defaultData.userName = 'enjeux';
                jeoelement.jeoCityAutoComplete();
                var elementExists = document.getElementById("mapdiv");
                if ( !elementExists )
                {
                  loadMapDiv("#element-265");
                }
                jeoelement.on("autocompleteselect", function(event, ui) {geocode(ui.item.value)});
              }
              else if (element.is('select')) {
                element.addClass('selectpicker');
                element.children("option[value='']").remove();
                element.selectpicker({style: 'btn-success', title: 'Choisir ci-dessous'});
              }
              else if (!element.hasClass("use-html-checkbox") && !element.hasClass("fileinput")) {
                element.addClass("form-control");
              }
            });
        });

        // When an add button is clicked, make an AJAX request to add another input.
        context.find(addSelector).off('click').click(function (event) {
            event.preventDefault();
            var fieldDiv = $(this).parents(fieldSelector);
            // NOTE I added it

            params = {};
            fieldDiv.find('input, textarea, select').each(function () {
                var element = $(this);
                // Workaround for annoying jQuery treatment of checkboxes.
                if (element.is('[type=checkbox]')) {
                  params[this.name] = element.is(':checked') ? '1' : '0';
                }
                else {
                    // Make sure TinyMCE saves to the textarea before we read
                    // from it
                    if (element.is('textarea')) {
                        var mce = tinyMCE.get(this.id);
                        if (mce) {
                            mce.save();
                        }
                    }
                    params[this.name] = element.val();
                }
            });

            var elementId = fieldDiv.attr('id').replace(/element-/, '');
            var response = fieldDiv.find('.input-block:last').clone();
            fieldDiv.find('.inputs').append(response);

            // Change ids and names in the new block
            var newInputBlock = fieldDiv.find('.input-block:last');
            newInputBlock.find('input, textarea, select').each(function () {
              var element = $(this);
              if ( !element.hasClass( "remove-element" )) {
                // Increase the id for new input block
                var oldId = element.attr('name').replace(/Elements\[[0-9]*]\[/, '').replace(/\]\[[a-z]*\]/, '');
                var newId = parseInt(oldId) + 1;
                if ( element.attr('id') ) {
                  if ( element.attr('id').endsWith('text') ) {
                    element.attr('id', 'Elements-' + elementId + '-' + newId + '-text');
                  }
                  else if ( element.attr('id').endsWith('html') ) {
                    element.attr('id', 'Elements-' + elementId + '-' + newId + '-html');
                  }
                }
                if ( element.attr('name').endsWith('[text]') ) {
                  element.attr('name', 'Elements[' + elementId + ']' + '[' + newId + '][text]');
                }
                else if ( element.attr('name').endsWith('[html]') ) {
                  element.attr('name', 'Elements[' + elementId + ']' + '[' + newId + '][html]');
                }
              }
            });
            fieldDiv.trigger('omeka:elementformload');

            // NOTE I did it to avoid loading login page when clicking without having logged in
            //Omeka.Elements.elementFormRequest(fieldDiv, {add: '1'}, elementFormPartialUrl, recordType, recordId);
        });

        // When a remove button is clicked, remove that input from the form.
        context.find(removeSelector).click(function (event) {
            event.preventDefault();
            var removeButton = $(this);

            // Don't delete the last input block for an element.
            if (removeButton.parents(fieldSelector).find(inputBlockSelector).length === 1) {
                return;
            }

            if (!confirm('Do you want to delete this input?')) {
                return;
            }

            var inputBlock = removeButton.parents(inputBlockSelector);
            inputBlock.find('textarea').each(function () {
                tinyMCE.execCommand('mceRemoveControl', false, this.id);
            });
            inputBlock.remove();

            // Hide remove buttons for fields with one input.
            $(fieldSelector).each(function () {
                var removeButtons = $(this).find(removeSelector);
                if (removeButtons.length === 1) {
                    removeButtons.hide();
                }
            });
        });
    };

    /**
     * Enable the WYSIWYG editor for "html-editor" fields on the form, and allow
     * checkboxes to create editors for more fields.
     *
     * @param {Element} element The element to search at and below.
     */
    Omeka.Elements.enableWysiwyg = function (element) {
        $(element).find('div.inputs .use-html-checkbox').each(function () {
            var textarea = $(this).parents('.input-block').find('textarea');
            if (textarea.length) {
                var textareaId = textarea.attr('id');
                var enableIfChecked = function () {
                    if (this.checked) {
                        tinyMCE.execCommand("mceAddControl", false, textareaId);
                    } else {
                        tinyMCE.execCommand("mceRemoveControl", false, textareaId);
                    }
                };

                enableIfChecked.call(this);

                // Whenever the checkbox is toggled, toggle the WYSIWYG editor.
                $(this).click(enableIfChecked);
            }
        });
    };
})(jQuery);
