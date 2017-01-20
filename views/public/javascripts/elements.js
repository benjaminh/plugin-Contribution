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
    var marker;
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


        // Remove DC basic explanations
        $('.explanation').hide();

        // Load openlayer div
        loadMapDiv = function(elementId) {
          var locationElement = jQuery(elementId);
          html = '<div id="mapdiv"></div>';
          locationElement.after(html);


          map = new L.map('mapdiv');
          map.getSize();
          map.invalidateSize();
          // create the tile layer with correct attribution

          var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
          var osmAttrib='Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
          var osm = new L.TileLayer(osmUrl, {minZoom: 3, maxZoom: 15, attribution: osmAttrib});

          // start the map in South-East England
          map.addLayer(osm);
          map.setView([47.212277, -1.5386562], 11, {
            reset: true,
            animate: true
          });
          map.invalidateSize();

          // NOTE Leaflet seems to have an issue with unexpected behaviour during the first loading of the map. Here is a working hack for my case
          $('#collapseOne').one('mouseenter' , function( event ) {
            map.setView([46.52863469527167,2.43896484375], 6, {
              reset: true,
              animate: true
            });
            map.invalidateSize();
          });
        }

        // Geocode function
        function geocode(address)
        {

          console.log(address);
          if (typeof marker !== 'undefined')
          {
            map.removeLayer(marker);
          }
          var openStreetMapGeocoder = GeocoderJS.createGeocoder('openstreetmap');
          openStreetMapGeocoder.geocode(address, function(result) {
            var latlng = new L.LatLng(result[0]['latitude'], result[0]['longitude']);
            marker = new L.marker([result[0]['latitude'], result[0]['longitude']]);
            map.addLayer(marker);
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

            if ($(this).attr('id') == "element-1") {
              $(this).find('div:first-child label').text("Vous souhaitez donner plus de détails sur cet événement : utilisez l'espace ci-dessous");
              var textElem = $(this).find('label.use-html').contents().filter(function(){ return this.nodeType == 3; });
              textElem.remove();
              $(this).find('label.use-html').prepend("Activer l'éditeur avancé");
            }
            else if ($(this).attr('id') == "element-257") {
              // Check if field already exists - seems to be called 2 times - don't know why yet FIXME
              if ($('#type-select').length === 0) {
                html = `
                <select name="type-select" id='type-select'>
                  <optgroup label="Séjour familial">
                    <option value="camping">Campings</option>
                    <option value="chambre">Chambres d'hôtes</option>
                    <option value="club">Clubs et villages de vacances</option>
                    <option value="famille">Dans la famille</option>
                    <option value="gites">Gîtes et assimilés (meublés classés de tourisme)</option>
                    <option value="hotel">Hôtels</option>
                  </optgroup>
                  <optgroup label='Séjour organisé'>
                    <option value="org-auberge">Auberges de jeunesse</option>
                    <option value="org-camping">Campings</option>
                    <option value="org-chambre">Chambres d'hôtes</option>
                    <option value="org-classe">Classes découverte</option>
                    <option value="org-club">Clubs et villages de vacances</option>
                    <option value="org-colonie">Colonies de vacances</option>
                    <option value="org-accueil">Famille d'accueil</option>
                  </optgroup>
                </select>
                `;
                $("#Elements-257-0-text").hide();
                $(this).find('div.input').append(html);
                $('#type-select').on('change', function(event) {
                  var selected = $("#type-select").val();
                  var group = '';
                  if ( selected.startsWith('org-') ) {
                    group = 'Séjour organisé'
                  }
                  else {
                    group = "Séjour familial";
                  }
                  $("#Elements-257-0-text").val(group + ', ' + $('#type-select option:selected').text());
                });
              }
            }
            else if ($(this).attr('id') == "element-264") {
              // Check if field already exists - seems to be called 2 times - don't know why yet FIXME
              if ($('#status-select').length === 0) {
                html = `
                <select name="status-select" id='status-select'>
                  <option value="part">Participant</option>
                  <optgroup label='Organisateur'>
                    <option value="org-ben">Bénévole</option>
                    <option value="org-sal">Salarié</option>
                  </optgroup>
                  <optgroup label='Encadrant sur place'>
                    <option value="enc-ben">Bénévole</option>
                    <option value="enc-sal">Salarié</option>
                  </optgroup>
                  <option value="fin">Financeur/Mécène</option>
                  <option value="interm">Je n'ai pas participé à cet événement</option>
                  <option value="autre">Autre</option>
                </select>
                `;
                $("#Elements-264-0-text").hide();
                $(this).find('div.input').prepend(html);
                $('#status-select').on( 'change', function(event) {
                  var selected = $("#status-select").val();
                  var group = '';
                  $("#Elements-264-0-text").hide();
                  if ( selected.startsWith('org-') ) {
                    group = 'Organisateur';
                  }
                  else if ( selected.startsWith('enc-') ) {
                    group = 'Encadrant sur place';
                  }
                  if ( selected == 'autre' ) {
                    $('#Elements-264-0-text').show();
                  }
                  else if ( group != '' ) {
                    $("#Elements-264-0-text").val(group + ', ' + $('#status-select option:selected').text());
                  }
                  else {
                    $("#Elements-264-0-text").val($('#status-select option:selected').text());
                  }
                });
              }
            }
            else if ($(this).attr('id') == "element-261") {
              // Date picker TODO avoid HARDCODING
              // Separate fields for year, month and display
              // Save answer in hidden field
              $( "#Elements-261-0-text" ).hide();
              var radioButtons = `
              <p class="explanation-info">1. Sélectionner le type d'événément</p>
              <div class="btn-group">
                <label class="btn"><input checked type="radio" name="optDate" value="1"/>Événément sur une journée</label>
                <label class="btn"><input type="radio" name="optDate" value="2"/>Événement sur plusieurs jours</label>
                <label class="btn"><input type="radio" name="optDate" value="3"/>Date inconnue</label>
              </div>
              <p class="explanation-info">2. Sélectionner le format de la date "Année", "Mois/Année", "Jour/Mois/Année"</p>
              <div class="funkyradio date-format">
                <div class="funkyradio-info">
                  <input id="y" type="radio" name="dateFormat" value='y'><label for="y" class="radio-inline">Année</label>
                </div>
                <div class="funkyradio-info">
                  <input id="m" type="radio" name="dateFormat" value='m'><label for="m" class="radio-inline">Mois/Année</label>
                </div>
                <div class="funkyradio-info">
                  <input id="d" type="radio" name="dateFormat" value='d'><label for="d" class="radio-inline">Jour/Mois/Année</label>
                </div>
              </div>
              `;
              var dateFormat = `
              <div id="datepicker1" style="display: none;">
                <p class="explanation-info">3. Cliquer sur le champ de saisie puis sélectionner la date à l'aide du calendrier</p>
                <input type="text" class="form-control">
              </div>
              <div id="datepicker2" style="display: none;">
              <p class="explanation-info">3. Cliquer sur chaque champ de saisie puis sélectionner la date à l'aide du calendrier</p>
                <input type="text" class="form-control"/>
                <span class="input-group-addon">au</span>
                <input type="text" class="form-control"/>
              </div>
              `;
              if (!$( "#datepicker1" ).length) {
                $( "#Elements-261-0-text" ).parent().append(radioButtons);
                $( "#Elements-261-0-text" ).parent().append(dateFormat);
              }
              $('input:radio[name="optDate"]').on('change', function (event) {
                // Hide datepickers
                $('#datepicker1, #datepicker2').hide();
                var filterDate = $('input:radio[name="optDate"]:checked').val();
                if (filterDate == 1) {
                  //$('#datepicker2').hide();
                  //$('#datepicker1').show();
                  $('#element-261 div.btn-group').show();
                }
                else if (filterDate == 2) {
                  // From date 1 to date 2
                  //$('#datepicker2').show();
                  //$('#datepicker1').hide();
                  $('#element-261 div.btn-group').show();
                }
                else if (filterDate == 3) {
                  $('#datepicker1, #datepicker2').hide();
                  //$('#element-261 div.date-format').hide();
                  $('#element-261 div.date-format').hide();
                  $( "#Elements-261-0-text" ).val("Date inconnue");
                }
              });
              var container = $('#element-261 div.input');
              $('#datepicker1 input').datepicker( {
                startDate: "1600",
                format: "yyyy",
                autoclose: true,
                container: container,
                minViewMode: 2,
                language: "fr"
              });
              $('#datepicker1 input').datepicker("setEndDate", new Date());
              $.each($("#datepicker2 input"), function() {
                $(this).datepicker({
                  startDate: "1600",
                  format: "yyyy",
                  autoclose: true,
                  container: container,
                  minViewMode: 2,
                  language: "fr"
                });
              });

              $('input:radio[name="dateFormat"]').on('change', function (event) {

                // Display datepickers based on event type
                var filterDate = $('input:radio[name="optDate"]:checked').val();
                if (filterDate == 1) {
                  $('#datepicker2').hide();
                  $('#datepicker1').show();
                }
                else if (filterDate == 2) {
                  // From date 1 to date 2
                  $('#datepicker2').show();
                  $('#datepicker1').hide();
                }

                console.log($("input[name='dateFormat']:checked").val());
                if ( $("input[name='dateFormat']:checked").val() == "d" ) {
                  $('#datepicker1 input').datepicker("clearDates");
                  $('#datepicker1 input').datepicker('setFormat', 'dd/mm/yyyy');
                  $('#datepicker1 input').datepicker('setStartDate', '01/01/1600');
                  $('#datepicker1 input').datepicker('setMinViewMode', 0);
                  $.each($("#datepicker2 input"), function() {
                    $(this).datepicker('setStartDate', '01/01/1600');
                    $(this).datepicker("clearDates");
                    $(this).datepicker('setFormat', 'dd/mm/yyyy');
                    $(this).datepicker('setMinViewMode', 0);
                  });
                }
                else if ( $("input[name='dateFormat']:checked").val() == "m" ) {
                  $('#datepicker1 input').datepicker("clearDates");
                  $('#datepicker1 input').datepicker('setFormat', 'mm/yyyy');
                  $('#datepicker1 input').datepicker('setMinViewMode', 1);
                  $('#datepicker1 input').datepicker('setStartDate', '01/1600');
                  $.each($("#datepicker2 input"), function() {
                    $(this).datepicker('setStartDate', '01/1600');
                    $(this).datepicker("clearDates");
                    $(this).datepicker('setFormat', 'mm/yyyy');
                    $(this).datepicker('setMinViewMode', 1);
                  });
                }
                else if ( $("input[name='dateFormat']:checked").val() == "y" ) {
                  $('#datepicker1 input').datepicker("clearDates");
                  $('#datepicker1 input').datepicker('setFormat', 'yyyy');
                  $('#datepicker1 input').datepicker('setStartDate', '1600');
                  $('#datepicker1 input').datepicker('setMinViewMode', 2);
                  $.each($("#datepicker2 input"), function() {
                    $(this).datepicker('setStartDate', '1600');
                    $(this).datepicker("clearDates");
                    $(this).datepicker('setFormat', 'yyyy');
                    $(this).datepicker('setMinViewMode', 2);
                  });
                }
              });

              // Update DC field with new date
              $('#datepicker1 input, #datepicker2 input').on('change', function() {
                var filterDate = $('input:radio[name="optDate"]:checked').val();
                if (filterDate == 1) {
                  var date = $('#datepicker1 input').val();
                  $('#Elements-261-0-text').val(date);
                }
                else if (filterDate == 2) {
                  // From date 1 to date 2
                  var date1 = $('#datepicker2 input:first-child').val();
                  var date2 = $('#datepicker2 input:last-child').val();
                  $('#Elements-261-0-text').val('Du ' + date1 + ' au ' + date2);
                }
              });
            }
            else if ($(this).attr('id') == "element-200") {
              // Hide the license field. Populate it with cc Chooser
              $(this).hide();
            }
            else if ($(this).attr('id') == "element-254") {
              // Hide the Date de création de l'organisme → travail de collation a posteriori
              $(this).hide();
            }
            else if ($(this).attr('id') == "element-51") {
              // Hide the Type de document → travail de collation a posteriori (EAD type)
              $(this).hide();
            }
            else if ($(this).attr('id') == "element-199") {
              // Hide the Access Rights element. Populate with license chooser info
              $(this).hide();
            }
            else if ($(this).attr('id') == "element-41") {
              $('#Elements-41-0-text').attr('placeholder', "Décrivez le contenu du document, à quoi se rapporte t-il ?");
            }
            else if ($(this).attr('id') == "element-260") {
              $(this).hide();
              var selected_doc_type = $('#contribution-type button.active');
              $('#Elements-260-0-text').val(selected_doc_type.attr("value"));
            }
            else if ($(this).attr('id') == "element-262") {

              if ($('#age-select').length === 0) {
                html = `
                <select name="age-select" id='age-select' multiple>
                  <option value="mat">Maternelle (3-6 ans)</option>
                  <option value="ce">Élémentaire (6-9 ans)</option>
                  <option value="cm">Cours Moyen (9-11 ans)</option>
                  <option value="college">Collège (11-15 ans)</option>
                  <option value="lycee">Lycée (15-18 ans)</option>
                  <option value="18">>18 ans</option>
                  <option value="idk">Ne sais pas</option>
                </select>
                `;
                $("#Elements-262-0-text").hide();
                $(this).find('div.input').append(html);
                $('#age-select').on('change', function(event) {
                  setTimeout( function () {
                    var selected = $('button[data-id="age-select"]').attr('title');
                    console.log(selected);
                    $('#Elements-262-0-text').val(selected);
                  }, 10);
                });
                $('#age-select').attr("multiple", "multiple");
              }
            }
            else if ($(this).attr('id') == "element-50") {
              // Hide the Access Rights element. Populate with license chooser info
              $(this).hide();
              var titre = $('#Elements-260-0-text').val();
              $('#Elements-50-0-text').val(titre);
            }
            else if ( $(this).attr('id') == "element-266") {
              // Touring stay or not
              var toHide = $('#element-266 div.input-block');
              var itinerantStart = $('#element-271').hide();
              var itinerantArrival = $('#element-272').hide();
              if ( !$('#itinerant').length ) {
                html = `
                  <label for="itinerant">Était-ce un séjour itinérant ?</label>
                  <input name="itinerant" value="0" type="hidden">
                  <input id="itinerant" name="itinerant" value="1" style="display: none;" type="checkbox">
                `;
                $(this).children(":last").prepend(html);
                $('#itinerant').bootstrapSwitch();
                $('#itinerant').on('switchChange.bootstrapSwitch', function(event, state) {
                  if (state) {
                    toHide.hide();
                    $('#mapdiv').hide();
                    itinerantStart.show();
                    itinerantArrival.show();
                  }
                  else {
                    toHide.show();
                    $('#mapdiv').show();
                    itinerantStart.hide();
                    itinerantArrival.hide();
                  }
                });
              }
            }
            else if ( ($(this).attr('id') == "element-267") || ($(this).attr('id') == "element-268") || ($(this).attr('id') == "element-269") || ($(this).attr('id') == "element-270") ) {
              $(this).hide();
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
              // Add geonames API query using jeoquery or google maps api
              // TODO AVOID HARDCODING
              else if (element.attr('id') == "Elements-266-0-text" || element.attr('id') == "Elements-271-0-text" || element.attr('id') == "Elements-272-0-text") {

                // USING GOOGLE MAPS API
                var placeSearch, autocomplete;
                var componentForm = {
                  locality: 'long_name',
                  administrative_area_level_1: 'short_name',
                  country: 'long_name',
                  postal_code: 'short_name'
                };
                var mapping = {
                  locality: 'Elements-268-0-text',
                  administrative_area_level_1: 'Elements-269-0-text',
                  country: 'Elements-270-0-text',
                  postal_code: 'Elements-267-0-text'
                };
                // TODO AVOID HARDCODING
                element.attr("placeholder", "Saisissez un début d'adresse");
                // Create the autocomplete object, restricting the search to geographical
                // location types.
                autocomplete = new google.maps.places.Autocomplete(
                // TODO AVOID HARDCODING
                    /** @type {!HTMLInputElement} */(document.getElementById(element.attr('id'))),
                    {types: ['geocode']});
                // When the user selects an address from the dropdown, populate the address
                // fields in the form.
                if (element.attr('id') == 'Elements-266-0-text') {
                  autocomplete.addListener('place_changed', fillInAddress);
                }
                else {
                  autocomplete.addListener('place_changed', callGeocode);
                }
                function callGeocode() {
                  //geocode(element.value);
                }
                // [START region_fillform]
                function fillInAddress() {
                  // Get the place details from the autocomplete object.
                  var place = autocomplete.getPlace();

                  for (var component in componentForm) {
                    mappedID = mapping[component];
                    document.getElementById(mappedID).value = '';
                    document.getElementById(mappedID).disabled = false;
                  }

                  // Get each component of the address from the place details
                  // and fill the corresponding field on the form.
                  for (var i = 0; i < place.address_components.length; i++) {
                    var addressType = place.address_components[i].types[0];
                    if (componentForm[addressType]) {
                      var val = place.address_components[i][componentForm[addressType]];
                      mappedID = mapping[addressType];
                      document.getElementById(mappedID).value = val;
                    }
                  }
                  // Place a marker on the map
                  // TODO AVOID HARDCODING
                  geocode(document.getElementById('Elements-266-0-text').value);
                }
                var elementExists = document.getElementById("mapdiv");
                if ( !elementExists )
                {
                  // TODO AVOID HARDCODING
                  loadMapDiv("#element-270");
                }
              }
              else if (element.is('select')) {
                element.addClass('selectpicker');
                element.children("option[value='']").remove();
                element.selectpicker({style: 'btn-primary', title: 'Sélectionner dans la liste'});
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
