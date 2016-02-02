<?php

require_once('custom/CustomHandlers/Global_Functions.php');
require_once('custom/CustomHandlers/DoceboConnector.php');

class S_Sites_AccessHandler extends Global_Functions {
	

	/**
	Create Joomla User in the site
	**/
	function Create_User_In_Joomla($bean, $event, $arguments)
	{
		global  $sugar_config, $GLOBALS;

		//CHECK IF THIS IS THE A NEW SL ACCOUNT FOR THE CLIENT AND THERE IS NO JOOMLA ID, IF YES, CREATE A JOOMLA USER
		if((empty($bean->fetched_row) || (!empty($bean->fetched_row) && $bean->joomla_id == 0 && $bean->create_user == 1)) && $bean->site == $sugar_config['default_joomla_site'])
		{
			$clientId = $this->_get_related_id($bean,'contacts_s_sites_access_1contacts_ida','contacts_s_sites_access_1');
			$leadId = $this->_get_related_id($bean,'leads_s_sites_access_1leads_ida','leads_s_sites_access_1');

            $DoceboID = 0;

			if($leadId != ''){
				$module_name = 'Leads';
        		$contactObj = BeanFactory::getBean('Leads');			
				$contactObj->retrieve($leadId);

			}
			if($clientId != ''){
				$module_name = 'Contacts';
        		$contactObj = BeanFactory::getBean('Contacts');
				$contactObj->retrieve($clientId);

                $params = array(
                    'where' => array(
                        'lhs_field' => 'site',
                        'operator' => '=',
                        'rhs_value' => $sugar_config['docebo']['url'],
                    ),
                );

                $contactObj->load_relationship('contacts_s_sites_access_1');
                $SiteAccesses = $contactObj->contacts_s_sites_access_1->getBeans($params);
                if(!empty($SiteAccesses))
                {
                    $SiteAccess = current($SiteAccesses);
                    $DoceboID = $SiteAccess->joomla_id;
                }
			}

			if($bean->password == ' ' || $bean->password == ''){
				$password = $this->createPassword(6);
				$bean->password = $password;
			}else{
				$password = $bean->password;
			}

			$arrPOST = array(
					'auth_user' => $sugar_config['joomla_username'],
					'auth_pass' => $sugar_config['joomla_password'],
					'call' => 'manage_user',
					'action' => 'create_user',
					'username'=>$bean->name,
					'email'=>$bean->name,
					'password'=>$password,
					'name'=>$contactObj->first_name .' '.$contactObj->last_name,
					'sugar_id'=>$contactObj->id,
					'sugar_module'=>$module_name,
                    'docebo_id' => $DoceboID,
			);

			$joomlaResult = $this->_doRESTCALL($sugar_config['joomla_url_prefix'].$sugar_config['default_joomla_site'].$sugar_config['joomla_url'], $arrPOST);
			if(empty($joomlaResult->errors))
			{
				$bean->joomla_id = $joomlaResult->id;
                $GLOBALS['log']->info('Create_User_In_Joomla : user created in Joomla '.print_r($joomlaResult,true));
			}else{
				$GLOBALS['log']->error('Create_User_In_Joomla : Create user in Joomla failed '.print_r($arrPOST,true).' ERROR:'.$joomlaResult->errors[0]->message);
			}
		}
	}


    function Create_User_In_Docebo($bean, $event, $arguments)
    {
        global  $sugar_config, $GLOBALS;

        //CHECK IF THIS IS THE A NEW SL ACCOUNT FOR THE CLIENT AND THERE IS NO JOOMLA ID, IF YES, CREATE A JOOMLA USER
        if((empty($bean->fetched_row) || (!empty($bean->fetched_row) && $bean->joomla_id == 0 && $bean->create_user == 1)) && $bean->site == $sugar_config['docebo']['url'])
        {
            $clientId = $this->_get_related_id($bean,'contacts_s_sites_access_1contacts_ida','contacts_s_sites_access_1');
            $leadId = $this->_get_related_id($bean,'leads_s_sites_access_1leads_ida','leads_s_sites_access_1');

            if($leadId != ''){
                $module_name = 'Leads';
                $contactObj = BeanFactory::getBean('Leads');
                $contactObj->retrieve($leadId);
            }
            if($clientId != ''){
                $module_name = 'Contacts';
                $contactObj = BeanFactory::getBean('Contacts');
                $contactObj->retrieve($clientId);
            }


            if($bean->password == ' ' || $bean->password == ''){
                $password = $this->createPassword(6);
                $bean->password = $password;
            }else{
                $password = $bean->password;
            }

            $Docebo = new DoceboAPI($sugar_config['docebo']);

            $arrPOST = array(
                'userid'    => $bean->name,
                'firstname' => $contactObj->first_name,
                'lastname'  => $contactObj->last_name,
                'password'  => $bean->password,
                'email'     => $bean->name,
                'ext_user_type'  => 'SugarCRM',
                'ext_user' => 0,
                'role' => 'student',
            );

            $checkDocebo = $Docebo->call('user/checkUsername', array('userid'=>$bean->name));
            $checkDocebo = json_decode($checkDocebo);
            if($checkDocebo->success == 1 && isset($checkDocebo->idst))
            {
                $idSt = $checkDocebo->idst;
            }
            else
            {
                $createDocebo = $Docebo->call('user/create', $arrPOST);
                $createDocebo = json_decode($createDocebo);
                $idSt = $createDocebo->idst;
            }


            if($createDocebo->success === true || $checkDocebo->success === true)
            {
                $bean->joomla_id = $idSt;
                $bean->password = '';
		  $bean->create_user = 0;
                // RE-CREATE User enrollments in Docebo
                $result = $bean->db->query("  SELECT uag.id,uag.name,uag.website, uag.joomla_id FROM s_sites_access_uag_user_access_group_2_c su, uag_user_access_group uag
                                            WHERE su.s_sites_access_uag_user_access_group_2s_sites_access_ida='{$bean->id}' AND su.deleted=0
                                          and su.s_sites_access_uag_user_access_group_2uag_user_access_group_idb = uag.id and uag.deleted = 0 and uag.website = '{$bean->site}'
                                            union
                                            SELECT uag.id,uag.name,uag.website, uag.joomla_id FROM s_sites_access_uag_user_access_group_1_c su, uag_user_access_group uag
                                              WHERE su.s_sites_access_uag_user_access_group_1s_sites_access_ida='{$bean->id}' AND su.deleted=0
                                            and su.s_sites_access_uag_user_access_group_1uag_user_access_group_idb = uag.id and uag.deleted = 0 and uag.website = '{$bean->site}'");

                while ($row = $bean->db->fetchByAssoc($result)) {
                    $arrPOST = array(
                        'idst' => $bean->joomla_id,
                        'course_id' => $row['joomla_id'],
                        'user_level' => 'student',
                    );

                    $createDocebo = $Docebo->call('course/addUserSubscription', $arrPOST);
                    $createDocebo = json_decode($createDocebo);
                    if ($createDocebo->success === true) {
                        $GLOBALS['log']->info('Enroll_User_In_Docebo : User successfully RE enrolled in Docebo course ' . $row['name']);
                    } else {
                        $GLOBALS['log']->error('Enroll_User_In_Docebo : RE Enroll user in Docebo failed. Params: ' . print_r($arrPOST, true) . ' ERROR: ' . print_r($createDocebo, true));
                    }
                }

            }else{
                $GLOBALS['log']->error('Create_User_In_Docebo : Create user in Docebo failed. Params: '.print_r($arrPOST,true).' ERROR: '.print_r($createDocebo,true));
            }

        }

    }
	
    /**
    * Push user site access to Joomla Site.
		-When a site access is altered/updated, the data will be pushed to joomla user profile.
    */	
	function Push_Site_Access_To_Sites($bean, $event, $arguments)
	{
		global  $sugar_config,$GLOBALS;
        	$GLOBALS['log']->info('Push_Site_Access_To_Sites: '.$bean->name.' run hook on event: '.$event);

		if(!empty($bean->fetched_row) && $bean->block == 1 && $bean->fetched_row['block'] == 1)
            $bean->block = 0;

		if($bean->site == $sugar_config['default_joomla_site'] && !$bean->block )
        {
             $result = $bean->db->query("select uag_link.group_id, uag.joomla_id, uag.website from
                                          (SELECT s_sites_access_uag_user_access_group_1uag_user_access_group_idb as group_id FROM s_sites_access_uag_user_access_group_1_c WHERE s_sites_access_uag_user_access_group_1s_sites_access_ida='{$bean->id}' AND deleted=0
                                        UNION
                                           SELECT s_sites_access_uag_user_access_group_2uag_user_access_group_idb FROM s_sites_access_uag_user_access_group_2_c WHERE s_sites_access_uag_user_access_group_2s_sites_access_ida='{$bean->id}' AND deleted=0
                                          ) uag_link,  uag_user_access_group uag
                                          where uag_link.group_id = uag.id  and uag.website = '{$sugar_config['default_joomla_site']}'");
            $joomlaIds = "";
            while($row = $bean->db->fetchByAssoc($result)){
                if($row['joomla_id'] != '' || $row['joomla_id'] != NULL)
                    $joomlaIds .= $row['joomla_id'].'*';
            }
            $GLOBALS['log']->info('Push_Site_Access_To_Sites: '.$bean->name.' site '.$bean->site.' Joomla Ids '.$joomlaIds);

            if(!empty($joomlaIds))
            {
                $arrPOST = array(
                    'auth_user' => $sugar_config['joomla_username'],
                    'auth_pass' => $sugar_config['joomla_password'],
                    'call' => 'manage_user',
                    'action' => 'update_usergroup',
                    'username' => $bean->name,
                    'user_group' => $joomlaIds
                );

                $joomlaResult = $this->_doRESTCALL($sugar_config['joomla_url_prefix'].$sugar_config['default_joomla_site'].$sugar_config['joomla_url'], $arrPOST);
                if(!empty($joomlaResult->errors))
                {
                    $GLOBALS['log']->error('Push_Site_Access_To_Sites : Pushing site access to site failed '.print_r($arrPOST,true).' ERROR:'.$joomlaResult->errors[0]->message);
                }
                else
                    $GLOBALS['log']->info('Push_Site_Access_To_Sites: Successful response from Joomla: '.print_r($joomlaResult,true));

            }
        }
        elseif($bean->site == $sugar_config['docebo']['url'] && !empty($event) && $event != 'after_save')
        {
            $GLOBALS['log']->info('Push_Site_Access_To_Sites: '.$bean->name.' run hook on event: '.$event);
            $clientId = $this->_get_related_id($bean,'contacts_s_sites_access_1contacts_ida','contacts_s_sites_access_1');
            $leadId = $this->_get_related_id($bean,'leads_s_sites_access_1leads_ida','leads_s_sites_access_1');

            if($leadId != ''){
                $module_name = 'Leads';
                $contactObj = BeanFactory::getBean('Leads');
                $contactObj->retrieve($leadId);
            }
            if($clientId != ''){
                $module_name = 'Contacts';
                $contactObj = BeanFactory::getBean('Contacts');
                $contactObj->retrieve($clientId);
            }

            if(isset($arguments['related_id']) && !empty($arguments['related_id']))
                 $UAG = BeanFactory::getBean('UAG_user_access_group',$arguments['related_id']);
            else $UAG = false;

            $GLOBALS['log']->info('Push_Site_Access_To_Sites: '.$bean->name.' site '.$bean->site.' Platform group '.$UAG->joomla_id);

            if($UAG !== false && $bean->joomla_id != 0)
            {
                $Docebo = new DoceboAPI($sugar_config['docebo']);

                $arrPOST = array(
                    'id_user'    => $bean->joomla_id,
                    'course_id'  => $UAG->joomla_id,
                    'user_level' => 'student'
                );

                if($event == 'after_relationship_add')
                    $createDocebo = $Docebo->call('course/addUserSubscription', $arrPOST);
                elseif($event == 'after_relationship_delete')
                    $createDocebo = $Docebo->call('course/deleteUserSubscription', $arrPOST);

                $createDocebo = json_decode($createDocebo);
                if($createDocebo->success === true)
                {
                    $GLOBALS['log']->info('Enroll_User_In_Docebo : User access successfully updated for Docebo course '.$UAG->name);
                }else{
                    $GLOBALS['log']->error('Enroll_User_In_Docebo : User access update FAILED in Docebo. Params: '.print_r($arrPOST,true).' ERROR: '.print_r($createDocebo,true));
                }
            }
        }
        else
            $GLOBALS['log']->error('Push_Site_Access_To_Sites: Cannot recognise platform or SA Object blocked: Site access '.$bean->name.', platform: '.$bean->site.' Blocked = '.$bean->block.' Event: '.$event);
	}	
	
}
?>