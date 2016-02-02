<?php

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

class Global_Functions
{

    var $debug = false; // turn off by default!
    var $debugLogLevel = 'warn'; # debug is too verbose to find anything!! use warn instead
    var $accounts_email = null;
    var $template_from_email = null;
    var $template_from_name = null;
    var $exit_on_error = true;

	function __construct()
	{
		if($_SERVER['SCRIPT_URL'] == '/service/tpg_v1/rest.php')
			$this->REST_CALL = TRUE;
		else
			$this->REST_CALL = FALSE;
	}
    /*
     * if its a fatal failure pass an error
     * else if you are just exiting and displaying a message to the user
     * use $this->_exit(null,$msg) to avoid it being logged to debug log
     */
    function _exit($msg='') {
		global $sugar_config,$GLOBALS;

		if($_SERVER['SCRIPT_URL'] != '/service/tpg_v1/rest.php' || $_SERVER['SCRIPT_NAME'] != '/service/tpg_v1/rest.php')
		{
			$GLOBALS['log']->fatal('ERROR MESSAGE:'.$msg);
			die('<script type="text/javascript">alert("'.$msg.'");window.parent.location = "'.$sugar_config['site_url'].'/index.php?module='.$_REQUEST['module'].'&action=EditView&record='.$_REQUEST['record'].'&return_module='.$_REQUEST['return_module'].'&return_id='.$_REQUEST['return_id'].'&return_action=DetailView";</script>');
		}else{
			$GLOBALS['log']->fatal('ERROR MESSAGE:'.$msg);
			$result = array('name' => 'An Undefined Error',
							'number' => -1,
							'description' => $msg);
			print_r($result);
			die();
		}
			
    }
	
    function _fetch($sql,$db) {
        if (($result = $db->query($sql,true)) === false) {
            $this->_debug('DB Error: ' . $sql.' - '.mysql_error() . ', file: ' . __FILE__ . ', line: ' . __LINE__);
            // return false;
        }
	    $return = array();
        // loop through rows
        while ($row = $db->fetchByAssoc($result)) {
            $return[] = $row;
        }
        return $return;
    }
	
    function _debug($msg) {
        $GLOBALS['log']->{$this->debugLogLevel}($msg);
    }
	
	function createPassword($length) {
		$chars = "234567890abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$i = 0;
		$password = "";
		while ($i <= $length) {
			$password .= $chars{mt_rand(0,strlen($chars))};
			$i++;
		}
		return $password;
	}
	
	
	function _doRESTCALL($url,$data) {

		$ch = curl_init();
	
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_POST, 1);
		
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0 );
		
		$post_data = 'input_type=json&response_type=json';
		$jsonEncodedData = json_encode($data, false);
		$post_data = $post_data . "&rest_data=" . $jsonEncodedData;
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		$result = curl_exec($ch);
		curl_close($ch);
		$result = explode("\r\n\r\n", $result, 2);
		$response_data = json_decode($result[1]);
		return $response_data;

	}	
	
	
	
	function _get_related_id($bean,$field,$rel)
	{
		$Id = '';
		if(is_object($bean->$field)){
			$IdArr = $bean->$rel->beans;
			foreach($IdArr as $key=>$id)
				$Id =$key;
		}else{
			$Id = $bean->$field;
		}
		
		if($Id == '')
			$Id = $bean->rel_fields_before_value[$field];
			
		return $Id;
	}
	
	
	function _has_permission($userid,$bean,$role)
	{
		$result = $bean->db->query("SELECT r.name FROM acl_roles_users as u,acl_roles as r WHERE u.user_id='".$userid."' AND r.id=u.role_id AND u.deleted=0 AND r.deleted=0 AND r.name='".$role."'");
		if($row=$bean->db->fetchByAssoc($result))
			return true;
		else
			return false;	
	}

    function substr_count_array( $haystack, $needle ) {
        $count = 0;
        foreach ($needle as $substring) {
            $count += substr_count( $haystack, $substring);
        }
        return $count;
    }
	
	function _Send_Email($fromAddress,$fromName,$toAddresses,$subject,$module,$bean_id,$body,$attachedFiles=array(),$saveCopy=true)
	{
		global $current_user, $sugar_config;
		if($sugar_config['dbconfig']['db_host_name'] != '10.2.1.20' && $sugar_config['dbconfig']['db_host_name'] != '127.0.0.1')
            $send_ok = false;
        else $send_ok =  true;

        //Replace general variables for all email templates.
        $keys = array('$contact_name','$contact_first_name','$sales_full_name','$sales_first_name');
        $vars_count = $this->substr_count_array($body,$keys);
        if(($module == 'Contacts' || $module == 'Leads') && $vars_count > 0){
            $clientObj = BeanFactory::getBean($module,$bean_id);
            $sale_person = $this->_getSalesPerson($clientObj);
            $data = array($clientObj->first_name.' '.$clientObj->last_name,$clientObj->first_name,$sale_person['sales_full_name'],$sale_person['sales_first_name']);
            $body = str_replace($keys,$data,$body);
        }
        //if(!$send_ok) $GLOBALS['log']->error('Mail Service: not a Live Server, trashmail accounts service only. ');
		
		$emailObj = new Email();
		$defaults = $emailObj->getSystemDefaultEmail();
	
		$mail = new SugarPHPMailer();
		$mail->setMailerForSystem();
		$mail->From = $fromAddress;
		$mail->FromName = $fromName;
		$mail->Subject = $subject;
		$mail->Body = $body;
		$mail->ContentType = "text/html";
		$mail->prepForOutbound();

        $test_addr = false;
		foreach($toAddresses as $name => $email)
        {
			$mail->AddAddress($email, $name);
            if(substr_count($email, '@trashmail') > 0 || $email == 'dsmikhal@gmail.com') $test_addr = true;
        }

        if($send_ok || $test_addr)
        {
            if(!empty($attachedFiles)){
                foreach($attachedFiles as $files){
                 $mail->AddAttachment($files['file_location'].$files['filename'], $files['filename'], 'base64');
                }
            }

            if(@$mail->Send())
            {

                if($saveCopy)
                {
                    $emailObj->from_addr = $fromAddress;
                    $emailObj->reply_to_addr = implode(',',$toAddresses);
                    $emailObj->to_addrs = implode(',',$toAddresses);
                    $emailObj->name = $subject;
                    $emailObj->type = 'out';
                    $emailObj->status = 'sent';
                    $emailObj->intent = 'pick';
                    $emailObj->parent_type = $module;
                    $emailObj->parent_id = $bean_id;
                    $emailObj->description_html = $body;
                    $emailObj->description = $body;
                    $emailObj->assigned_user_id = $current_user->id;
                    $emailObj->save();

                    if(!empty($attachedFiles))
                    {
                        foreach($attachedFiles as $files){
                            $Notes = BeanFactory::getBean('Notes');
                            $Notes->name = $files['filename'];
                            $Notes->file_mime_type = 'pdf';
                            $Notes->filename = $files['filename'];
                            $Notes->parent_type = 'Emails';
                            $Notes->parent_id = $emailObj->id;
                            $Notes->save();

                            $pdf = file_get_contents($files['file_location'].$files['filename']);

                            file_put_contents('upload/'.$Notes->id, $pdf);
                        }
                    }
                }
                return true;
            }else {
                $GLOBALS['log']->info("Mailer error: " . $mail->ErrorInfo);
                return false;
            }
        }
        else
        {
            $GLOBALS['log']->error('Mail Service: not a Live Server('.$sugar_config['dbconfig']['db_host_name'].'), trashmail accounts service only. Cannot send mail to '. print_r($toAddresses,true));
            $emailObj->from_addr = $fromAddress;
            $emailObj->reply_to_addr = implode(',',$toAddresses);
            $emailObj->to_addrs = implode(',',$toAddresses);
            $emailObj->name = 'TEST MODE, NOT SENT: '.$subject;
            $emailObj->type = 'out';
            $emailObj->status = 'NOT sent';
            $emailObj->intent = 'pick';
            $emailObj->parent_type = $module;
            $emailObj->parent_id = $bean_id;
            $emailObj->description_html = $body;
            $emailObj->description = $body;
            $emailObj->assigned_user_id = $current_user->id;
            $emailObj->save();
            return false;
        }
	}


    function _getDateTime($datestr)
    {
        $datestr = str_replace('/','-',$datestr);
        $date =  Date_create($datestr);
        $date->getTimestamp();
        return $date->format('Y-m-d');
    }

    function _getDisplayDate($datestr)
    {
        $datestr = str_replace('/','-',$datestr);
        $date =  Date_create($datestr);
        $date->getTimestamp();
        return $date->format('d-m-Y');
    }

    function _updateClientClass($bean, $clientId, $module)
    {
        $str = strtolower($module);
        if($clientId != '')
        {
            $result = $bean->db->query("SELECT e.product_class FROM e_events as e,".$str."_e_enrolments_1_c as link,e_enrolments as enrol,e_events_e_enrolments_1_c as elink WHERE link.".$str."_e_enrolments_1".$str."_ida='".$clientId."' AND link.deleted=0 AND enrol.id=link.".$str."_e_enrolments_1e_enrolments_idb AND enrol.deleted=0 AND elink.e_events_e_enrolments_1e_enrolments_idb=enrol.id AND elink.deleted=0 AND e.id=elink.e_events_e_enrolments_1e_events_ida AND (enrol.status='yes' OR enrol.status='confirmed' OR enrol.status='PartPaid') ");
            $class = array();
            while($row=$bean->db->fetchByAssoc($result)){
                $pclasStr = str_replace('^,^','^',$row['product_class']);
                $pclass = explode('^',$pclasStr);
                foreach($pclass as  $p){
                    if($p != '')
                        $class[$p] = '^'.$p.'^';
                }
            }

            if($module == 'Contacts')
            {
                $result = $bean->db->query("select m.name, ti.name,ti.product_class FROM mems_membership m, trann_items ti, contacts_mems_membership_1_c cm
                    WHERE (m.status = 'Active' OR m.status = 'Suspended') AND ti.id=m.trann_items_id_c AND cm.contacts_mems_membership_1mems_membership_idb=m.id
                    AND cm.contacts_mems_membership_1contacts_ida = '".$clientId."'
                    AND m.deleted=0 AND ti.deleted=0 AND cm.deleted=0
                    AND ti.product_class LIKE 'SLCM%'");
                while($row=$bean->db->fetchByAssoc($result)){
                    if($row['product_class'] != '')
                        $class[$row['product_class']] = '^'.$row['product_class'].'^';

                }
            }

            if(empty($class))
                $classString = '';
            else
                $classString =  implode(',',$class);

            $ClientObj = BeanFactory::getBean($module,$clientId);
            if(substr_count($classString,'Online') != 0 && $ClientObj->affiliate_type_c == 'Affiliate')
            {
                $ClientObj->affiliate_type_c = 'Affiliate_2015';
            }

            $ClientObj->client_class_c = $classString;
            $ClientObj->save();
        }
    }

    function _checkProductGroups($client_id,$ProductObj)
    {
        //$ProductObj = BeanFactory::getBean('Trann_Items',$product_id);

        $ProductObj->load_relationship('trann_items_trann_product_bundle_2');
        $ProductGroup = $ProductObj->trann_items_trann_product_bundle_2->getBeans();

        if(!empty($ProductGroup))
        {
            $result = array('group' => true);

            foreach($ProductGroup as $ProductBundle)
            {
                if($ProductBundle->activate_on == 'JoinNew')
                    $Prod['JoinNew'][] = $ProductBundle->trann_items_id_c;

                elseif($ProductBundle->activate_on == 'JoinExt')
                    $Prod['JoinExt'][] = $ProductBundle->trann_items_id_c;

                elseif($ProductBundle->activate_on == 'Suspend')
                    $Prod['Suspend'][] = $ProductBundle->trann_items_id_c;

                elseif($ProductBundle->activate_on == 'Neglect')
                    $Prod['Neglect'][] = $ProductBundle->trann_items_id_c;
            }

            foreach($Prod as $type => $group)
            {
                switch ($type){
                    case "JoinNew":
                        $sql = $ProductObj->db->query("select m.id,m.trann_items_id_c,m.expirydate,m.next_billing_date from contacts_mems_membership_1_c cm, mems_membership m
                          where cm.deleted = 0 and cm.contacts_mems_membership_1mems_membership_idb = m.id and m.trann_items_id_c in ('".implode("','",$Prod['JoinNew'])."')
                          and cm.contacts_mems_membership_1contacts_ida = '{$client_id}' and m.status in ('Active','Suspended', 'PendingRenewal','OverDue') LIMIT 1");
                        if($mem = $ProductObj->db->fetchByAssoc($sql))
                            $result['JoinNew'] = $mem;
                        break;
                    case "JoinExt":
                        $txtSql = "select m.id,m.trann_items_id_c,m.expirydate,m.next_billing_date, m.name from contacts_mems_membership_1_c cm, mems_membership m
                          where cm.deleted = 0 and cm.contacts_mems_membership_1mems_membership_idb = m.id and m.trann_items_id_c in ('".implode("','",$Prod['JoinExt'])."')
                          and cm.contacts_mems_membership_1contacts_ida = '{$client_id}' and m.status in ('Active','Suspended', 'PendingRenewal','OverDue') LIMIT 1";

                        $sql = $ProductObj->db->query("select m.id,m.trann_items_id_c,m.expirydate,m.next_billing_date, m.name from contacts_mems_membership_1_c cm, mems_membership m
                          where cm.deleted = 0 and cm.contacts_mems_membership_1mems_membership_idb = m.id and m.trann_items_id_c in ('".implode("','",$Prod['JoinExt'])."')
                          and cm.contacts_mems_membership_1contacts_ida = '{$client_id}' and m.status in ('Active','Suspended', 'PendingRenewal','OverDue') LIMIT 1");
                        if($mem = $ProductObj->db->fetchByAssoc($sql))
                            $result['JoinExt'] = $mem;
                        break;
                    case "Suspend":
                        $sql = $ProductObj->db->query("select m.id,m.trann_items_id_c,m.expirydate,m.next_billing_date, m.name from contacts_mems_membership_1_c cm, mems_membership m
                          where cm.deleted = 0 and cm.contacts_mems_membership_1mems_membership_idb = m.id and m.trann_items_id_c in ('".implode("','",$Prod['Suspend'])."')
                          and cm.contacts_mems_membership_1contacts_ida = '{$client_id}' and m.status in ('Active','Suspended', 'PendingRenewal','OverDue')");
                        while($mem = $ProductObj->db->fetchByAssoc($sql))
                            $result['Suspend'][] = $mem;
                        break;
                    case "Neglect":
                        $sql = $ProductObj->db->query("select m.id,m.trann_items_id_c,m.expirydate,m.next_billing_date, m.name from contacts_mems_membership_1_c cm, mems_membership m
                          where cm.deleted = 0 and cm.contacts_mems_membership_1mems_membership_idb = m.id and m.trann_items_id_c in ('".implode("','",$Prod['Neglect'])."')
                          and cm.contacts_mems_membership_1contacts_ida = '{$client_id}' and m.status in ('Active','Suspended', 'PendingRenewal','OverDue')");
                        if($mem = $ProductObj->db->fetchByAssoc($sql))
                            $result['Neglect'] = $mem;
                        break;
                }
            }
        }
        else $result = false ;

        return $result;
    }


    function _parseParams($params)
    {
        $p = explode("&",$params);
        $parse = array();
        foreach($p as $value)
        {
            $p1 = explode("=",$value);
            $parse[$p1[0]] = $p1[1];
        }
        return $parse;
    }

    function _getSalesPerson($clientObj)
    {
        if(!empty($clientObj->assigned_user_id))
        {
            $salesPerson = BeanFactory::getBean('Users',$clientObj->assigned_user_id);
            if($salesPerson->first_name == 'Admin' || $salesPerson->empoyee_status == 'Terminated')
            {
                $sales_person['sales_full_name'] = $this->EMAIL_DEFAULT_SIGN;
                $sales_person['sales_first_name'] = $this->EMAIL_DEFAULT_SIGN;
            }
            else
            {
                $sales_person['sales_full_name'] = $salesPerson->first_name . ' ' . $salesPerson->last_name;
                $sales_person['sales_first_name'] = $salesPerson->first_name;
            }
        }
        else{
            $sales_person['sales_full_name'] = $this->EMAIL_DEFAULT_SIGN;
            $sales_person['sales_first_name'] = $this->EMAIL_DEFAULT_SIGN;
        }

        return $sales_person;
    }

	
}
?>