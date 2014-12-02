<?php
class Api_ContributionType extends Omeka_Record_Api_AbstractRecordAdapter
{
    
    public function getRepresentation(Omeka_Record_AbstractRecord $type)
    {
        $representation = array(
                'id'               => $type->id,
                'url'              => self::getResourceUrl("/contribution_types/{$ype->id}"),
                'display_name'     => $type->display_name,
                'file_permissions' => $type->file_permissions
                );
        $representation['item_type'] = array(
                'id'  => $type->item_type_id,
                'url' => self::getResourceUrl("/item_types/{$type->item_type_id}")
                );
        return $representation;
    }
    
    public function setPostData(Omeka_Record_AbstractRecord $record, $data)
    {}
    
    public function setPutData(Omeka_Record_AbstractRecord $record, $data)
    {}
}