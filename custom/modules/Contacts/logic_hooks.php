<?php
//$hook_array['before_save'][] = array(10,'workflow','include/workflow/WorkFlowHandler.php','WorkFlowHandler','WorkFlowHandler',); 
$hook_array['before_save'][] = array(2,'Update User In Joomla','custom/CustomHandlers/Contacts/ContactsHandler.php','ContactsHandler','Update_User_In_Joomla',);
$hook_array['before_save'][] = array(1,'RCNUM','custom/CustomHandlers/RCNUM_Record_Numbering/RCNUM_Record_NumberingHandler.php','RecordNumbering','RCNUM_Record_Numbering',);

$hook_array['after_save'][] = array(2,'Update Docebo Course progress','custom/CustomHandlers/Contacts/ContactsHandler.php','ContactsHandler','update_Course_Progress',);
$hook_array['after_save'][] = array(1,'Create Site Access','custom/CustomHandlers/Contacts/ContactsHandler.php','ContactsHandler','Create_Site_Access',);
