<?php
/**
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @copyright Center for History and New Media, 2010
 * @package Contribution
 * @subpackage Models
 */

/**
 * Record for individual contributor metadata.
 *
 * @package Contribution
 * @subpackage Models
 */
class ContributionContributorValue extends Omeka_Record_AbstractRecord implements Zend_Acl_Resource_Interface
{
    public $field_id;
    public $contributor_id;
    public $value;
    
    public function getResourceId()
    {
        return 'Contribution_ContributorValue';
    }
}
