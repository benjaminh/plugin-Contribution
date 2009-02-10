<?php 
/**
 * Notes on correct usage:
 * This plugin will not work correctly if one or more of the following item Types has been removed:
 *		Document
 *		Still Image
 *		Moving Image
 * 		Sound
 *
 * Also, it will not work correctly if the Document type does not have a metafield called Text,
 * which is a default setting in Omeka.  This is because the "story" for the item is stored in the Text field of a Document.
 *
 * The text of the 'rights' field is stored in the views/public/contribution/consent.php file, and it should be edited for each project.
 *
 * @author CHNM
 * @version $Id$
 * @copyright CHNM, 2007-2008
 * @package Contribution
 **/

define('CONTRIBUTION_PLUGIN_VERSION', 0.2);
// Define this migration constant to help with upgrading the plugin.
define('CONTRIBUTION_MIGRATION', 1);
define('CONTRIBUTION_PAGE_PATH', 'contribution/');
define('CONTRIBUTORS_PER_PAGE', 10);

add_plugin_hook('define_routes', 'contribution_routes');
add_plugin_hook('config_form', 'contribution_config_form');
add_plugin_hook('config', 'contribution_config');
add_plugin_hook('install', 'contribution_install');
add_plugin_hook('initialize', 'contribution_initialize');
add_plugin_hook('define_acl', 'contribution_acl');

add_filter('public_navigation_main', 'contribution_public_main_nav');
add_filter('admin_navigation_main', 'contribution_admin_nav');

add_filter(array('Form', 'Item', 'Contribution Form', 'Posting Consent'), 'contribution_posting_consent_form');
add_filter(array('Form', 'Item', 'Contribution Form', 'Submission Consent'), 'contribution_submission_consent_form');
add_filter(array('Form', 'Item', 'Contribution Form', 'Online Submission'), 'contribution_is_online_submission_form');

add_filter(array('Display', 'Item', 'Dublin Core', 'Contributor'), 'contribution_show_anonymous_contributor');

function contribution_routes($router)
{
	// get the base path
	$bp = get_option('contribution_page_path');

    $router->addRoute('contributionAdd', new Zend_Controller_Router_Route($bp, array('module' => 'contribution', 'controller'=> 'index', 'action'=>'add')));
    
	$router->addRoute('contributionLinks', new Zend_Controller_Router_Route($bp . ':action', array('module' => 'contribution', 'controller'=> 'index')));    
}

function contribution_install()
{	
	$db = get_db();
	
	contribution_build_element_set(true);

	$db->exec("CREATE TABLE IF NOT EXISTS `$db->Contributor` (
			`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`entity_id` BIGINT UNSIGNED NOT NULL ,
			`birth_year` YEAR NULL,
			`gender` TINYTEXT NULL,
			`race` TINYTEXT NULL,
			`occupation` TINYTEXT NULL,
			`zipcode` TINYTEXT NULL,
			`ip_address` TINYTEXT NOT NULL
			) ENGINE = MYISAM ;");
		
	set_option('contribution_plugin_version', CONTRIBUTION_PLUGIN_VERSION);
	set_option('contribution_page_path', CONTRIBUTION_PAGE_PATH);
	set_option('contribution_require_tos_and_pp', FALSE);
	set_option('contribution_db_migration', CONTRIBUTION_MIGRATION);
}

function contribution_build_element_set($buildElements=true)
{
    try {
        $elementSet = new ElementSet;
	    $elementSet->name = "Contribution Form";
	    $elementSet->description = "The set of elements containing metadata from the Contribution form.";

	    if ($buildElements) {
	        $elementSet->addElements(array(
	             array(
	                'name'=>'Online Submission',
	                'description'=>'Indicates whether or not this Item has been contributed from a front-end contribution form.',
	                'record_type'=>'Item'),
	             array(
	                 'name'=>'Posting Consent',
	                 'description'=>'Indicates whether or not the contributor of this Item has given permission to post this to the archive. (Yes/No)',
	                 'record_type'=>'Item'),
	             array(
	                 'name'=>'Submission Consent',
	                 'description'=>'Indicates whether or not the contributor of this Item has given permission to submit this to the archive. (Yes/No)',
	                 'record_type'=>'Item')));
	    }

	    // Die if this doesn't save properly.
	    $elementSet->forceSave();
    } catch (Exception $e) {
        var_dump($e);exit;
    }
    return $elementSet->id;
}

function contribution_convert_existing_elements()
{
    // Retrieve the existing elements and modify them to belong to the Contribution Form element set.
    $db = get_db();
    $sql  = "SELECT id FROM $db->ElementSet WHERE name = 'Additional Item Metadata'";
    $additionalItemElementSetId = $db->fetchOne($sql);

    // If the Additional Item Metadata element set does not exist for whatever
    // reason, there is no pre-existing plugin data to convert so just build
    // the whole thing from scratch.  Otherwise just make the new element set
    // w/o the new elements and convert the old ones.
    $newElementSetId = contribution_build_element_set(!$additionalItemElementSetId);

    // Update the existing elements w/o interacting with the ElementSet models.
    try {
        $db->query(
	            "UPDATE $db->Element SET element_set_id = ? 
	            WHERE element_set_id = ? AND name IN (" . $db->quote(
	                array('Online Submission', 'Posting Consent', 'Submission Consent')) .
	            ") LIMIT 3",
	            array($newElementSetId, $additionalItemElementSetId));
    } catch (Exception $e) {
        var_dump($e);exit;
    }
}

function contribution_config_form()
{
	$textInputSize = 30;
	$textAreaRows = 10;
	$textAreaCols = 50;
	?>
	
	<div class="field">
	<label for="contribution_page_path">Relative Page Path From Project Root:</label>
	<div class="inputs">
	    <input type="text" name="contribution_page_path" value="<?php echo settings('contribution_page_path'); ?>" size="<?php echo $textInputSize; ?>" />
    	<p class="explanation">Please enter the relative page path from the project root where you want the contribution page to be located. Use forward slashes to indicate subdirectories, but do not begin with a forward slash.</p>
	</div>
	</div>
	
	<div class="field">
	<label for="contributor_email">Contributor 'From' Email Address:</label>
	<div class="inputs">
	    <input type="text" name="contributor_email" value="<?php echo settings('contribution_notification_email'); ?>" size="<?php echo $textInputSize; ?>" />
    	<p class="explanation">Please enter the email address that you would like to appear in the 'From' field for all notification emails for new contributions.  Leave this field blank if you would not like to email a contributor whenever he/she makes a new contribution:</p>
	</div>
    </div>
    
    <div class="field">
	<label for="contribution_consent_text">Consent Text:</label>
	<div class="inputs">
	    <textarea id="contribution_consent_text" name="contribution_consent_text" rows="<?php echo $textAreaRows; ?>" cols="<?php echo $textAreaCols; ?>"><?php echo settings('contribution_consent_text'); ?></textarea>
    	<p class="explanation">Please enter the legal text of your consent form:</p>
	</div>
	</div>
	
	<div class="field">
	<label for="recaptcha_public_key">reCAPTCHA Public Key</label>
	<div class="inputs">
	    <input type="text" name="recaptcha_public_key" value="<?php echo settings('contribution_recaptcha_public_key') ?>" id="recaptcha_public_key" />
	    <p class="explanation">To enable CAPTCHA for the contribution form, please obtain a <a href="http://recaptcha.net/">ReCAPTCHA</a> API key and enter the relevant values.</p>
	</div>
	</div>
	
	<div class="field">
	<label for="recaptcha_private_key">reCAPTCHA Private Key</label>
	<div class="inputs">
	    <input type="text" name="recaptcha_private_key" value="<?php echo settings('contribution_recaptcha_private_key') ?>" id="recaptcha_private_key" />
	</div>
	</div>
<?php
}

function contribution_config($post)
{
    set_option('contribution_recaptcha_public_key', $_POST['recaptcha_public_key']);
    set_option('contribution_recaptcha_private_key', $_POST['recaptcha_private_key']);
	set_option('contribution_consent_text', $post['contribution_consent_text']);
	set_option('contribution_notification_email', $post['contributor_email']);
	set_option('contribution_page_path', $post['contribution_page_path']);
	set_option('contribution_require_tos_and_pp', (boolean)$post['contribution_require_tos_and_pp']);
	
	
	//if the page path is empty then make it the default page path
	if (trim(get_option('contribution_page_path')) == '') {
		set_option('contribution_page_path', CONTRIBUTION_PAGE_PATH);
	}	
}

function contribution_link_to_contribute($text, $options = array())
{
	echo '<a href="' . uri(array(), 'contributionAdd') . '" ' . _tag_attributes($options) . ">$text</a>";
}

function contribution_embed_consent_form() {
?>
	<form action="<?php echo uri(array('action'=>'submit'), 'contributionLinks'); ?>" id="consent" method="post" accept-charset="utf-8">

			<h3>Please read this carefully:</h3>
			
			<div id="contribution_consent">
				<p><?php echo settings('contribution_consent_text'); ?></p>

				<textarea name="contribution_consent_text" style="display:none;"><?php echo settings('contribution_consent_text'); ?></textarea>
			</div>
			
			<div class="field">
				<p>Please give your consent below</p>
				<div class="radioinputs"><?php echo radio(array('name'=>'contribution_submission_consent'), 
						array(	'Yes'		=> ' I Agree. Please include my contribution.',
								'No'		=> ' No, I do not agree.'), 'No'); ?></div>
			</div>
			
	
		<input type="submit" class="submitinput" name="submit" value="Submit" />
	</form>
<?php
}

/**
 * @internal The following implementation could be replaced with a call to
 * item() for the 1.0 release of the plugin
 * 
 * @param Item $item
 * @return boolean
 **/
function contribution_is_anonymous($item)
{
    list($postingConsentTextRecord) = $item->getElementTextsByElementNameAndSetName('Posting Consent', 'Contribution Form');
    return 'Anonymously' == $postingConsentTextRecord->getText();
}

function contribution_admin_nav($navArray) 
{
    if (has_permission('Contribution_Index', 'browse')) {
        // This section of the admin should use the default routing construction
        // mechanism in ZF, because otherwise pagination_links() will not recognize
        // the 'page' routing parameter that is in the pagination control.
        $navArray += array('Contributors'=> uri(array('module'=>'contribution', 'controller'=>'index', 'action'=>'browse'), 'default'));
    }
    return $navArray;
}

function contribution_public_main_nav($navArray) {
    $navArray['Contribute'] = uri(array(), 'contributionAdd');
    return $navArray;
}

/**
 * Use this initialize hook to check to see whether or not we need to upgrade the plugin.
 * 
 * @param string
 * @return void
 **/
function contribution_initialize()
{
    contribution_upgrade();
}

function contribution_upgrade()
{
    $pluginVersion = get_option('contribution_db_migration');
    if ($pluginVersion < CONTRIBUTION_MIGRATION) {
        contribution_convert_existing_elements();
        // Bump up the database's migration #
        set_option('contribution_db_migration', CONTRIBUTION_MIGRATION);
    }
}

function contribution_acl($acl)
{
    $acl->loadResourceList(array('Contribution_Index'=>array('browse', 'edit', 'delete')));
}

/**
 * A prototype of the insert_item() helper, which will be in the core in 1.0.
 *
 * @uses InsertItemHelper
 * @param array $itemMetadata 
 * @param array $elementTexts 
 * @return Item
 * @throws Omeka_Validator_Exception
 * @throws Exception
 **/
function contribution_insert_item($itemMetadata = array(), $elementTexts = array())
{
    require_once 'InsertItemHelper.php';
    // Passing null means this will create a new item.
    $helper = new InsertItemHelper(null, $itemMetadata, $elementTexts);
    $helper->run();
    return $helper->getItem();
}

/**
 * @see contribution_add_item()
 * @uses InsertItemHelper
 * @see InsertItemHelper::__construct()
 * @param Item|int $item Either an Item object or the ID for the item.
 * @param array $itemMetadata Set of options that can be passed to the item.
 * @param array $elementTexts
 * @return Item
 **/
function contribution_update_item($item, $itemMetadata = array(), $elementTexts = array())
{
    require_once 'InsertItemHelper.php';
    $helper = new InsertItemHelper($item, $itemMetadata, $elementTexts);
    $helper->run();
    return $helper->getItem();
}

function contribution_posting_consent_form($html, $inputNameStem, $consent, $options, $item, $element)
{
    return __v()->formSelect($inputNameStem . '[text]', $consent, null, array(''=>'Not Applicable', 'Yes'=>'Yes', 'No'=>'No', 'Anonymously'=>'Anonymously'));
}

function contribution_submission_consent_form($html, $inputNameStem, $consent, $options, $item, $element)
{
    return __v()->formSelect($inputNameStem . '[text]', $consent, null, array(''=>'Not Applicable', 'No'=>'No', 'Yes'=>'Yes'));
}

function contribution_is_online_submission_form($html, $inputNameStem, $consent, $options, $item, $element)
{
    return __v()->formSelect($inputNameStem . '[text]', $consent, null, array('No'=>'No', 'Yes'=>'Yes'));
}

function contribution_show_anonymous_contributor($contributorName, $item)
{
    // Always show the contributor's name if we're logged in through the admin.
    if (is_admin_theme()) {
        return $contributorName;
    }
    
    // Determine whether or not the Contributor's name is supposed to be
    // anonymous.
    $isAnonymous = contribution_is_anonymous($item);
    if ($isAnonymous) {
        return 'Anonymous';
    }

    return $contributorName;
}