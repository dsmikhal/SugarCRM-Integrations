<?php
$hook_version = 1;
$hook_array = Array(); 

$hook_array['before_save'] = Array();

$hook_array['before_save'][] = Array(1, 'Create User In Joomla', 'custom/CustomHandlers/S_Sites_Access/S_Sites_AccessHandler.php','S_Sites_AccessHandler', 'Create_User_In_Joomla');
$hook_array['before_save'][] = Array(2, 'Create User In Docebo', 'custom/CustomHandlers/S_Sites_Access/S_Sites_AccessHandler.php','S_Sites_AccessHandler', 'Create_User_In_Docebo');
$hook_array['before_save'][] = Array(20, 'workflow', 'include/workflow/WorkFlowHandler.php','WorkFlowHandler', 'WorkFlowHandler');

$hook_array['after_save'] = Array();
$hook_array['after_save'][] = Array(1, 'Push Site Access To Sites', 'custom/CustomHandlers/S_Sites_Access/S_Sites_AccessHandler.php','S_Sites_AccessHandler', 'Push_Site_Access_To_Sites');

$hook_array['after_relationship_add'] = Array(); 
$hook_array['after_relationship_add'][] = Array(1, 'Push Site Access To Sites', 'custom/CustomHandlers/S_Sites_Access/S_Sites_AccessHandler.php','S_Sites_AccessHandler', 'Push_Site_Access_To_Sites'); 

$hook_array['after_relationship_delete'] = Array(); 
$hook_array['after_relationship_delete'][] = Array(1, 'Push Site Access To Sites', 'custom/CustomHandlers/S_Sites_Access/S_Sites_AccessHandler.php','S_Sites_AccessHandler', 'Push_Site_Access_To_Sites'); 



?>