<?php

require_once('custom/CustomHandlers/Global_Functions.php');
require_once('custom/CustomHandlers/DoceboConnector.php');

class ContactsHandler extends Global_Functions {
	

   	/**
	* CREATE SITE ACCESS FOR NEW CONTACT
	**/	
	function Create_Site_Access($bean, $event, $arguments)
	{
		global $sugar_config;
		
		if(empty($bean->fetched_row) || (!empty($bean->fetched_row) && $bean->fetched_row['email1'] == '' && $bean->email1 != ''))
		{
			if(empty($bean->fetched_row))
                $result = $bean->db->query("SELECT id FROM s_sites_access WHERE site ='".$sugar_config['default_joomla_site']."' AND name='".$bean->email1."' AND deleted=0 LIMIT 1");
            elseif(!empty($bean->fetched_row))
                $result = $bean->db->query("SELECT id FROM s_sites_access WHERE site ='".$sugar_config['default_joomla_site']."' AND name='".$bean->fetched_row['email1']."' AND deleted=0 LIMIT 1");

			if($row = $bean->db->fetchByAssoc($result))
			{	
				$accessObj = BeanFactory::getBean('S_Sites_Access');
				$accessObj->retrieve($row['id']);
				$accessObj->contacts_s_sites_access_1contacts_ida = $bean->id;
				$accessObj->leads_s_sites_access_1leads_ida = '';
                $accessObj->name = $bean->email1;
				$accessObj->save();
			}else{
				$accessObj = BeanFactory::getBean('S_Sites_Access');
				$accessObj->contacts_s_sites_access_1contacts_ida = $bean->id;
				$accessObj->leads_s_sites_access_1leads_ida = '';
				$accessObj->name = $bean->email1;
				$accessObj->site = $sugar_config['default_joomla_site'];
				$accessObj->joomla_id = 0;
				$accessObj->create_user = 1;
				$accessObj->password = $bean->password_c;
				$accessObj->full_name = $bean->first_name.' '.$bean->last_name;
				$accessObj->save();	

			}
		}
	}

	
	function Update_User_In_Joomla($bean, $event, $arguments)
	{   
		global  $beanList, $beanFiles, $current_user,$sugar_config,$GLOBALS;
		
		$siteAccess = array();
				
		if($bean->module_dir == 'Leads' && $bean->converted ==0 )
		{
			$bean->load_relationship('leads_s_sites_access_1');
			$siteAccess = $bean->leads_s_sites_access_1->getBeans();

		}
		if($bean->module_dir == 'Contacts')
		{
			$bean->load_relationship('contacts_s_sites_access_1');
			if(!empty($bean->fetched_row) && $bean->fetched_row['email1'] != '' && $bean->email1 != '' && $bean->fetched_row['email1'] != $bean->email1) {

				$siteAccess = $bean->contacts_s_sites_access_1->getBeans();

				foreach ($siteAccess as $Site_Access) {
					$Site_Access->name = $bean->email1;
					//$Site_Access->create_user = 1;
					if ($bean->not_update_joomla_c)
						$Site_Access->block = 1;
					$Site_Access->save();
				}
			}
		}

		//UPDATE THE USER DATA IN JOOMLA IF CHANGED IN SUGAR
		if(!empty($siteAccess) && $_SERVER['SCRIPT_NAME'] != 'cron.php' )
		{	
							
			foreach($siteAccess as $SitesAccessObj)
			{

				if($SitesAccessObj->joomla_id != 0 && $SitesAccessObj->joomla_id != '' && $SitesAccessObj->site == $sugar_config['default_joomla_site'] && !$bean->not_update_joomla_c)
				{
					$arrPOST = array(
						'auth_user' => $sugar_config['joomla_username'],
						'auth_pass' => $sugar_config['joomla_password'],
						'call' => 'manage_user',
						'action' => 'update_user',
						'id' => $SitesAccessObj->joomla_id,
						'username'=>$bean->email1,
						'email'=>$bean->email1,
						'name'=>$bean->first_name .' '.$bean->last_name,
						'sugar_id' => $bean->id,
						'sugar_module'=>'Contacts',
					);
					$joomlaResult = $this->_doRESTCALL($sugar_config['joomla_url_prefix'].$sugar_config['default_joomla_site'].$sugar_config['joomla_url'], $arrPOST);

					if(!empty($joomlaResult->errors))
					{
						$GLOBALS['log']->error('Update_User_In_Joomla : Update user in Joomla failed '.print_r($arrPOST,true).' ERROR:'.$joomlaResult->errors[0]->message);
					}
				}
                elseif($SitesAccessObj->joomla_id != 0 && $SitesAccessObj->joomla_id != '' && $SitesAccessObj->site == $sugar_config['docebo']['url'] && ($bean->fetched_row['email1'] != $bean->email1 || $bean->fetched_row['first_name'] != $bean->first_name || $bean->fetched_row['last_name'] != $bean->last_name))
                {
                    $Docebo = new DoceboAPI($sugar_config['docebo']);

                    $arrPOST = array(
                        'id_user' => $SitesAccessObj->joomla_id,
                        'single_user' => 1,
                        'userid'    => $bean->email1,
                        'firstname' => $bean->first_name,
                        'lastname'  => $bean->last_name,
                        'email'     => $bean->email1,
                        'ext_user_type'  => 'SugarCRM',
                    );

                    $createDocebo = $Docebo->call('user/edit', $arrPOST);
                    $createDocebo = json_decode($createDocebo);
                    if($createDocebo->success === true)
                    {
                        $GLOBALS['log']->info('Update_User_In_Docebo : User {$SitesAccessObj->name} successfully updated in Docebo.');
                    }else{
                        $GLOBALS['log']->error('Update_User_In_Docebo : Update user in Docebo failed. Params: '.print_r($arrPOST,true).' ERROR: '.print_r($createDocebo,true));
                    }

                }

			}
		}

        if($bean->not_update_joomla_c) $bean->not_update_joomla_c = 0;
	}

}

