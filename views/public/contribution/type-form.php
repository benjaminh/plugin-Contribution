<?php echo js_tag('vendor/tiny_mce/tiny_mce'); ?>
<?php echo js_tag('elements'); ?>

<script type="text/javascript" charset="utf-8">
jQuery(window).load(function () {
    // Must run the element form scripts AFTER reseting textarea ids.
    jQuery(document).trigger('omeka:elementformload');

    Omeka.Items.enableAddFiles(<?php echo js_escape(__('Add Another File')); ?>);
    Omeka.Items.changeItemType(<?php echo js_escape(url("items/change-type")) ?><?php if ($id = metadata('item', 'id')) echo ', '.$id; ?>);
});

jQuery(document).bind('omeka:elementformload', function (event) {
    Omeka.Elements.makeElementControls(event.target, <?php echo js_escape(url('elements/element-form')); ?>,'Item'<?php if ($id = metadata('item', 'id')) echo ', '.$id; ?>);
    Omeka.Elements.enableWysiwyg(event.target);
});
//]]>
</script>

<div class="container-fluid">
  <!-- Bootstrap code inside container div -->

  <?php if (!$type): ?>
  <p><?php echo __('Vous devez choisir un type de contenu pour continuer.'); ?></p>
  <?php else: ?>
  <h2><?php echo __('Proposer un/une %s', $type->display_name); ?></h2>

  <?php
  if ($type->isFileRequired()):
      $required = true;
  ?>

  <div class="field">
      <div class="two columns alpha">
          <?php echo $this->formLabel('contributed_file', __('Envoyer un fichier')); ?>
      </div>
      <div class="inputs five columns omega">
          <?php echo $this->formFile('contributed_file', array('class' => 'fileinput')); ?>
      </div>
  </div>

  <?php endif; ?>

  <div id="accordion" class="panel-group" aria-multiselectable="true" role="tablist">
    <div class="panel panel-default">
      <div id="headingOne" class="panel-heading" role="tab">
        <h4 class="panel-title">
          <a aria-controls="collapseOne" aria-expanded="true" href="#collapseOne" data-parent="#accordion" data-toggle="collapse">Informations sur le contenu</a>
        </h4>
      </div>
      <div id="collapseOne" class="panel-collapse collapse" aria-labelledby="headingOne" role="tabpanel">
        <div class="panel-body">
          <div class="row">
            <div class="form-group col-md-12">
              <?php
              foreach ($type->getTypeElements() as $contributionTypeElement) {
                  echo $this->elementForm($contributionTypeElement->Element, $item, array('contributionTypeElement'=>$contributionTypeElement));
              }
              ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>


  </div>

    <?php
    if (!isset($required) && $type->isFileAllowed()):
    ?>
    <div class="field">
            <div class="two columns alpha">
                <?php echo $this->formLabel('contributed_file', __('Envoyer un fichier (Facultatif)')); ?>
            </div>
            <div class="inputs five columns omega">
                <?php echo $this->formFile('contributed_file', array('class' => 'fileinput')); ?>
            </div>
    </div>
    <?php endif; ?>

    <?php $user = current_user(); ?>
    <?php if(get_option('contribution_simple') && !$user) : ?>
    <div class="field">
        <div class="two columns alpha">
        <?php echo $this->formLabel('contribution_simple_email', __('Email (Required)')); ?>
        </div>
        <div class="inputs five columns omega">
        <?php
            if(isset($_POST['contribution_simple_email'])) {
                $email = $_POST['contribution_simple_email'];
            } else {
                $email = '';
            }
        ?>
        <?php echo $this->formText('contribution_simple_email', $email ); ?>
        </div>
    </div>

    <?php else: ?>
        <p><?php echo __('You are logged in as: %s', metadata($user, 'name')); ?>
    <?php endif; ?>
        <?php
        //pull in the user profile form if it is set
        if( isset($profileType) ): ?>

        <script type="text/javascript" charset="utf-8">
        //<![CDATA[
        jQuery(document).bind('omeka:elementformload', function (event) {
             Omeka.Elements.makeElementControls(event.target, <?php echo js_escape(url('user-profiles/profiles/element-form')); ?>,'UserProfilesProfile'<?php if ($id = metadata($profile, 'id')) echo ', '.$id; ?>);
             Omeka.Elements.enableWysiwyg(event.target);
        });
        //]]>
        </script>

            <h2 class='contribution-userprofile <?php echo $profile->exists() ? "exists" : ""  ?>'><?php echo  __('Your %s profile', $profileType->label); ?></h2>
            <p id='contribution-userprofile-visibility'>
            <?php if ($profile->exists()) :?>
                <span class='contribution-userprofile-visibility'><?php echo __('Show'); ?></span><span class='contribution-userprofile-visibility' style='display:none'><?php echo __('Hide'); ?></span>
                <?php else: ?>
                <span class='contribution-userprofile-visibility' style='display:none'><?php echo __('Show'); ?></span><span class='contribution-userprofile-visibility'><?php echo __('Hide'); ?></span>
            <?php endif; ?>
            </p>
            <div class='contribution-userprofile <?php echo $profile->exists() ? "exists" : ""  ?>'>
            <p class="user-profiles-profile-description"><?php echo $profileType->description; ?></p>
            <fieldset name="user-profiles">
            <?php foreach($profileType->Elements as $element)
            {
                echo $this->profileElementForm($element, $profile);
            }
            ?>
            </fieldset>
            </div>
            <?php endif; ?>
  <?php
  // Allow other plugins to append to the form (pass the type to allow decisions
  // on a type-by-type basis).
  fire_plugin_hook('contribution_type_form', array('type'=>$type, 'view'=>$this));
  ?>
  <?php endif; ?>
</div>
<!-- End of bootstrap container
<
