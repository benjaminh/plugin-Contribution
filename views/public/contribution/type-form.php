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

  <?php
  if (!$type) {
    echo '<p>';
    echo __('Vous devez choisir un type de contenu pour continuer.');
    echo '</p>';
  }
  else {
    /*
    if ($type->id == 3) {
      echo '<h4>';
      echo __('Déposer une %s', $type->display_name, 'en remplissant le formulaire ci-dessous');
      echo '</h4>';
    }
    else if ($type->id == 4) {
    echo '<h4>';
    echo __('Déposer un %s', $type->display_name, 'en remplissant le formulaire ci-dessous');
    echo '</h4>';
    }
    */
    $allowMultipleFiles = $type->multiple_files;
  ?>
  <br/>
  <!--<div id="expl-perso">
    <p class="explanation-info">
      <b>Afin de connaitre le contexte dans lequel s’inscrivait votre séjour de vacances, merci de renseigner les champs ci-dessous.</b>
    </p>
  </div>
-->
  <div id="expl-instit">
    <p class="explanation-info">
      <b>Afin de recueillir davantage d'informations sur votre contribution, merci de renseigner les champs ci-dessous.</b>
    </p>
  </div>

  <?php
  if ($type->isFileRequired()):
      $required = true;
  ?>
  <?php endif; ?>


  <div id="accordion" class="panel-group" aria-multiselectable="true" role="tablist">
    <div class="panel panel-default">
      <div id="headingOne" class="panel-heading" role="tab">
        <!--<h4 class="panel-title">
           <a aria-controls="collapseOne" aria-expanded="true" href="#collapseOne" data-parent="#accordion" data-toggle="collapse">Informations sur le contenu</a>
        </h4>
      -->
      </div>
      <!-- <div id="collapseOne" class="panel-collapse collapse" aria-labelledby="headingOne" role="tabpanel"> -->
      <div id="collapseOne" aria-labelledby="headingOne" role="tabpanel">
        <div class="panel-body">
          <div class="row">
            <div class="form-group col-md-12">
              <input id="contribution-type" name="contribution_type" value="<?php echo $type->id; ?>" style="display: none;">
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

    <div id="files-form" class="field drawer-contents">
        <div class="two columns alpha">
            <?php echo $this->formLabel('contributed_file', __('Vous souhaitez partager un document ? Cliquez sur le bouton « Parcourir »')); ?>
        </div>
        <div id="files-metadata" class="inputs five columns omega">
            <div id="upload-files" class="files">
                <?php echo $this->formFile($allowMultipleFiles ? 'contributed_file[0]' : 'contributed_file', array('class' => 'fileinput button')); ?>
                <p class="explanation"><?php echo __('The maximum file size is %s.', max_file_size()); ?></p>
            </div>
        </div>
    </div>


      <?php if ($allowMultipleFiles): ?>
      <script type="text/javascript" charset="utf-8">
          <?php if (!empty($preset)): ?>
            jQuery(window).load(function () {
                Omeka.Items.enableAddFiles(<?php echo js_escape(__('Add Another File')); ?>);
            });
          <?php else: ?>
            Omeka.Items.enableAddFiles(<?php echo js_escape(__('Add Another File')); ?>);
          <?php endif; ?>
      </script>
      <?php endif; ?>
    <?php endif; ?>


    <?php if ($type->add_tags) : ?>
    <div id="tag-form" class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('tags', __('Add Tags')); ?>
        </div>
        <div class="inputs five columns omega">
            <p id="add-tags-explanation" class="explanation"><?php echo __('Separate tags with %s', option('tag_delimiter')); ?></p>
            <?php echo $this->formText('tags', isset($tags) ? $tags : ''); ?>
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
  <?php } ?>
</div>
<!-- End of bootstrap container
<
