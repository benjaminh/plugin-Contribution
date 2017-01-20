<?php
/**
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @copyright Center for History and New Media, 2010
 * @package Contribution
 */

queue_js_file('contribution-public-form');
$contributionPath = get_option('contribution_page_path');
if(!$contributionPath) {
    $contributionPath = 'contribution';
}
queue_css_file('form');

//load bootstrap files
queue_css_file('bootstrap-iso');
queue_js_file('bootstrap.min');
queue_css_file('bootstrap-datepicker.min');
queue_js_file('bootstrap-datepicker');
queue_js_file('locales/bootstrap-datepicker.fr.min');
queue_js_file('bootstrap-switch.min');
queue_css_file('bootstrap-switch.min');

//load jeoquery library
queue_js_file('jeoquery');
queue_js_file('geocoder.min');
queue_js_file('leaflet');
queue_css_file('leaflet');
?>
<?php
//load user profiles js and css if needed
if (get_option('contribution_user_profile_type') && plugin_is_active('UserProfiles') ) {
    queue_js_file('admin-globals');
    queue_js_file('tiny_mce', 'javascripts/vendor/tiny_mce');
    queue_js_file('elements');
    queue_css_string("input.add-element {display: block}");
}

$head = array('title' => 'Participez au projet',
              'bodyclass' => 'contribution');
echo head($head); ?>
<script type="text/javascript">
// <![CDATA[
enableContributionAjaxForm(<?php echo js_escape(url($contributionPath.'/type-form')); ?>);
// ]]>
</script>

<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.10.0/css/bootstrap-select.min.css">

<!-- Latest compiled and minified JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.10.0/js/bootstrap-select.min.js"></script>

<div id="primary">
<?php echo flash(); ?>

    <h1>Portail documentaire du programme de recherche Enfance et Jeunesse</h1>

    <h2><?php echo $head['title']; ?></h2>

    <?php if(!get_option('contribution_simple') && !$user = current_user()) :?>
        <?php $session = new Zend_Session_Namespace;
              $session->redirect = absolute_url();
        ?>
        <p>Pour contribuer au projet, vous devez <a href='<?php echo url('guest-user/user/register'); ?>'>créer un compte</a> ou <a href='<?php echo url('guest-user/user/login'); ?>'>vous identifier</a>.</p>
        <p>Cette création de compte ne vous prendra que quelques secondes. Une fois qu'un administrateur aura validé votre compte, vous pourrez déposer votre document.</p>

        <img src="<?php echo img('schema_contributions_portail.png'); ?>" style="width: 100%;"/>

        <h4>Qui peut déposer ?</h4>
        <p>Tout le monde, que vous soyez un particulier, un chercheur ou que vous représentiez une institution ou une entreprise.</p>
        <h4>Qui aura accès à mon document ?</h4>
        <p>Lors du remplissage du formulaire de contribution, vous choisissez les conditions d'accès à votre document (accès public sous licence Creative Commons, accès restreint, accès privé)</p>
        <h4>À quoi ça sert ?</h4>
        <p>Cette collecte de documents permettra de mieux connaître les pratiques vacancières des enfants et des jeunes et de comprendre la manière dont le développement des
    mobilités de loisir a influencé la structuration des territoires, notamment l’évolution des zones touristiques. Cette expertise est cruciale pour accompagner les
    territoires dans leur politique d’aménagement.</p>

    <?php else: ?>
      <h4>À quoi ça sert ?</h4>
      <p>Cette collecte de documents permettra de mieux connaître les pratiques vacancières des enfants et des jeunes et de comprendre la manière dont le développement des
  mobilités de loisir a influencé la structuration des territoires, notamment l’évolution des zones touristiques. Cette expertise est cruciale pour accompagner les
  territoires dans leur politique d’aménagement.</p>
      <p>Si vous avez des difficultés à nous transmettre vos documents, n’hésitez pas à nous contacter : contact[AT]enfance-jeunesse.fr.</p>

        <form method="post" action="" enctype="multipart/form-data" class='bootstrap-iso'>
            <fieldset id="contribution-item-metadata">
                <?php
                  /* THIS SECTION ASKS FOR ELEMENT TYPE
                    IN OUR CASE, THERE IS NO CHOICE BUT ONE
                  */
                  ?>
                  <div class="inputs">
                    <p>Sélectionner le type de document que vous souhaitez déposer en cliquant sur le bouton correspondant.</p>
                    <div id="contribution-type" class="btn-toolbar">
                      <div class="btn-group" role="group">
                        <button title="Photographie, carte postale, témoignage libre" type="button" class="btn btn-primary" name="temoignage" value="Témoignage ou archive personnelle">Témoignage ou archive personnelle</button>
                      </div>
                      <div class="btn-group" role="group">
                        <button title="Compte rendu, devis, règlement intérieur, statuts d'association, documents techniques" type="button" class="btn btn-primary" name="docadm" value="Archive institutionnelle">Archive institutionnelle</button>
                      </div>
                    </div>
                      <!-- <label for="contribution-type"><?php //echo __("What type of item do you want to contribute?"); ?></label> -->
                      <?php //$options = get_table_options('ContributionType' ); ?>
                      <?php //$typeId = isset($type) ? $type->id : '' ; ?>
                      <?php //echo $this->formSelect( 'contribution_type', $typeId, array('multiple' => false, 'id' => 'contribution-type') , $options); ?>
                      <!-- <input type="submit" name="submit-type" id="submit-type" value="Select" /> -->
                  </div>

                  <?php
                  /* END OF MODIFIED SECTION */
                  ?>
                <div id="contribution-type-form">
                  <?php if(isset($type)) {
                    echo $this->partial('contribution/type-form.php');
                  }
                  ?>
                </div>
            </fieldset>

            <fieldset id="contribution-confirm-submit" <?php if (!isset($type)) { echo 'style="display: none;"'; }?>>
                <?php if(isset($captchaScript)): ?>
                    <div id="captcha" class="inputs"><?php echo $captchaScript; ?></div>
                <?php endif; ?>
                <p><?php echo __("In order to contribute, you must read and agree to the %s",  "<a href='" . contribution_contribute_url('terms') . "' target='_blank'>" . __('Terms and Conditions') . ".</a>"); ?></p>
                <div class="inputs">
                    <?php $agree = isset( $_POST['terms-agree']) ?  $_POST['terms-agree'] : 0 ?>
                    <?php echo $this->formLabel('terms-agree', __('I agree to the Terms and Conditions.')); ?>
                    <?php echo $this->formCheckbox('terms-agree', $agree, null, array('1', '0')); ?>
                </div>
                <div class="inputs">
                    <?php $public = isset($_POST['contribution-public']) ? $_POST['contribution-public'] : 0; ?>
                    <?php echo $this->formCheckbox('contribution-public', $public, null, array('1', '0')); ?>
                    <?php echo $this->formLabel('contribution-public', __('Publish my contribution on the web.')); ?>
                </div>
                <?php
                  // Shortcode Creative Commons chooser
                  echo $this->shortcodes('[ccc]');
                ?>
                <div class="inputs">
                    <?php $anonymous = isset($_POST['contribution-anonymous']) ? $_POST['contribution-anonymous'] : 0; ?>
                    <?php echo $this->formCheckbox('contribution-anonymous', $anonymous, null, array(1, 0)); ?>
                    <?php echo $this->formLabel('contribution-anonymous', __("Contribute anonymously.")); ?>
                </div>
                <?php echo $this->formSubmit('form-submit', __('Contribute'), array('class' => 'submitinput')); ?>
            </fieldset>
            <?php echo $csrf; ?>
        </form>
    <?php endif; ?>
</div>
<?php echo foot();
?>
<script type="text/javascript">
  // NOTE differs from original code
  // Add some style to element with bootstrap
  (function ($) {

    $(window).keydown(function(event){
    if(event.keyCode == 13) {
      event.preventDefault();
      return false;
    }
    });

    var select = $('#form-submit');
    select.addClass("btn btn-success");
    select.prop('disabled', true);

    var contributionType = $('#contribution-type');
    contributionType.children("option[value='']").remove();
    contributionType.selectpicker({style: 'btn-primary', title: 'Sélectionner dans la liste'});
    // Uncomment the 2 lines below if you have only 1 choice possible and set the value accordingly
    //contributionType.hide();
    //$('#contribution-type option[value=3]').prop('selected', true).change();

    $('#contribution-type button').tooltip();

    // Display Creative Commons Chooser when selecting 'public contribution'
    var publicCheckbox = $('#contribution-public');
    publicCheckbox.change(
      function() {
        $('#cc_widget_container').toggle();
      }
    );

    // Hide creative commons licenses if CC licence radio button not checked
    $('#cc_js_license_selected').hide();
    $('input[name="cc_js_want_cc_license"]').on("change", function() { licensesToggle(); } );
    function licensesToggle() {
      var ccRadioChecked = $('input[name="cc_js_want_cc_license"]:checked');
      if( ccRadioChecked.length == 0 ) {
         // None is selected

      } else {
         var whichOne = ccRadioChecked.val();
         if ( whichOne == 'sure' ) {
           $('#cc_js_required').show();
           $('#cc_js_no_license').hide();
           $('#cc_js_license_selected').show();
         }
         else {
           $('#cc_js_required').hide();
           $('#cc_js_no_license').show();
           $('#cc_js_license_selected').hide();
         }
      }
    }

    // Populate DC term License and Access Rights with new value
    $('#cc_js_result_uri').on('change', function() {
      var chosenLicense = $('#cc_js_result_uri').val();
      if (chosenLicense != '') {
        $("#Elements-200-0-text").val(chosenLicense);
      }
    });
    $('input[name=cc_js_no_lic]').on('change', function() {
      $('#cc_js_license_selected').hide();
      var accessRights = $('input[name=cc_js_no_lic]:checked').val();
      if (accessRights == 'tous-degrade') {
        $("#Elements-199-0-text").val('Version dégradée accessible à tous');
      } else if (accessRights == 'chercheurs')
      {
        $("#Elements-199-0-text").val('Document accessible uniquement aux chercheurs');
      }
    });

    // Modify "I agree with" checkbox with Yes/No
    $('#terms-agree').hide();
    $('#contribution-anonymous').parent().hide();
    $('#contribution-public').parent().hide();
    $.fn.bootstrapSwitch.defaults.onText = 'Oui';
    $.fn.bootstrapSwitch.defaults.offText = 'Non';
    $.fn.bootstrapSwitch.defaults.indeterminate = true;
    $('#terms-agree').bootstrapSwitch();

    $('#terms-agree').on('switchChange.bootstrapSwitch', function(event, state) {
      if (state) {
        $('#contribution-anonymous').parent().show();
        $('#contribution-public').parent().show();
        select.prop('disabled', false);
      }
      else {
        $('#contribution-anonymous').parent().hide();
        $('#contribution-public').parent().hide();
        select.prop('disabled', true);
      }
    });

  })(jQuery);
</script>

<!-- TEST-->
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDqWOqcV2uYiGSS4xH2WDwjP7zPMYA4DUQ&signed_in=true&libraries=places" async defer></script>
