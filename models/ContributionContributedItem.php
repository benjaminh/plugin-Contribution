<?php
/**
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @copyright Center for History and New Media, 2010
 * @package Contribution
 * @subpackage Models
 */

/**
 * Record that keeps track of contributions; links items to contributors.
 */
class ContributionContributedItem extends Omeka_Record_AbstractRecord implements Zend_Acl_Resource_Interface
{
    public $item_id;
    public $contributor_id;
    public $public;
    
    protected $_related = array(
        'Item' => 'getItem',
        'Contributor' => 'getContributor'
        );
    
    public function getItem()
    {
        return $this->getDb()->getTable('Item')->find($this->item_id);
    }

    public function getContributor()
    {
        return $this->getDb()->getTable('ContributionContributor')->find($this->contributor_id);
    }
    
    public function getResourceId()
    {
        return 'Contribution_ContributedItem';
    }
}
