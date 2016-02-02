<?php
if (!defined('sugarEntry')) define('sugarEntry', true);

/**
 * SugarWebServiceImpltp_v1.php
 *
 * This class is an implementation class for all the custom web services specific to TP, DTR and SL.  
 * get_client_lead_id function.  Returns the sugar id for a Lead or Client and type of Record
 * set_clients_or_leads finction . Creates/Updates and Returns client or lead record as per the passed email address
 *
 */
require_once('service/v4_1/SugarWebServiceImplv4_1.php');
require_once('service/v4_1/SugarWebServiceUtilv4_1.php');

class SugarWebServiceImpltpg_v1 extends SugarWebServiceImplv4_1
{

    /**
     * Class Constructor Object
     *
     */
    public function __construct()
    {
        self::$helperObject = new SugarWebServiceUtilv4_1();
    }

    /**
     * Retreives the Module and Record id, if passed email address exists in the system
     *
     * @param String $session -- Session ID returned by a previous call to login.
     * @param String $email -- Email address 
     * @return Array 'result' -- Array - Returns module,id(record id) and email
     */
    function get_client_lead_id($session, $email)
    {
        global  $beanList, $beanFiles;


		if (!self::$helperObject->validate_authenticated($session)) {
			$result = array('name' => 'Invalid Session ID',
					'number' => 11,
					'description' => "The session ID is invalid");
    		return $result;
    	}
		
    	$bean = BeanFactory::getBean('Leads');
		
		$result = $bean->db->query("SELECT rel.bean_id,rel.bean_module from email_addr_bean_rel as rel, email_addresses as email WHERE  email.email_address='".trim($email)."' AND rel.email_address_id = email.id AND email.deleted=0 AND rel.deleted=0 AND rel.bean_module='Contacts' LIMIT 1");
		if($row=$bean->db->fetchByAssoc($result)){
				$result = array('module' => 'Contacts',
								'id' => $row['bean_id'],
								'email' => trim($email));
		}else{
			$result = $bean->db->query("SELECT rel.bean_id,rel.bean_module from email_addr_bean_rel as rel, email_addresses as email WHERE  email.email_address='".trim($email)."' AND rel.email_address_id = email.id AND email.deleted=0 AND rel.deleted=0 AND rel.bean_module='Leads' LIMIT 1");
			if($row=$bean->db->fetchByAssoc($result)){
				$result = array('module' => 'Leads',
								'id' => $row['bean_id'],
								'email' => trim($email));
			}else{
				$result = array('module' => 'Leads',
								'id' => '',
								'email' => trim($email));
			}
		}
        

    	$GLOBALS['log']->info('End: SugarWebServiceImpl->get_client_lead_id');
    	return $result;
    }


     /** set_clients_or_leads
     * Updates a Client Record for the passed Email address
	 * If client record not found, update the Lead Record
	 * If lead record not found, creates a Lead Record
	 *
     * @param String $session -- Session ID returned by a previous call to login.
	 * @param String $email -- email address
     * @param String $name_value_list -- name value list of the data to be updated or created 
	 * Sameple Array
	 * $data = array( 
	 *			'session' => $session->id, 
	 *			'email' => 'test@test.com',
	 *			'name_value_list' => array( 
	 *				array('name' => 'first_name', 'value' => 'First Name'),
	 *				array('name' => 'last_name', 'value' => 'Last Name'),
	 *				array('name' => 'home_phone', 'value' => '023142525235'),  
	 *				array('name' => 'email1', 'value' => 'test@test.com'), 
	 *              array('name' => 'password_c', 'value' => 'dsfgdsfg346'), 	 
	 *			), 
	 *	 );
	 *	
     * @return Array 'result' -- Array - Returns list fields
     */
    function set_clients_or_leads($session, $email, $name_value_list=array())
	{
		global  $current_user;
				
		if (!self::$helperObject->validate_authenticated($session)) {
			$result = array('name' => 'Invalid Session ID',
					'number' => 11,
					'description' => "The session ID is invalid");
    		return $result;
    	}
		
		//GET module and id if record already exists
		$get_result = $this->get_client_lead_id($session, $email);
		
		//Add the id to the name_value_list
		if($get_result['id'] != '')
			$name_value_list[] = array('name' => 'id', 'value' => $get_result['id']);
		
        $result = $this->set_entry($session,$get_result['module'], $name_value_list, $track_view = FALSE);
		$result['module'] = $get_result['module'];
		
		$GLOBALS['log']->info('End: SugarWebServiceImpl->set_clients_or_leads');
      	return $result;
    }
	
	 /** set_case_record
     * Creates case Record and Relate with the client or Lead record as per the supplied email
	 * If there is no client/lead record found for the email address, api creates a Lead Record and associate
	 * the case with it. 
	 *
     *
     * @param String $session -- Session ID returned by a previous call to login.
	 * @param String $email -- email address 
     * @param String $name_value_list -- name value list of the data to be updated or created 
	 * Sameple Array
	 * $data = array( 
	 *			'session' => $session->id, 
	 *			'email' => 'test@test.com',
	 *			'name_value_list' => array( 
	 *				array('name' => 'name', 'value' => 'New Case'),
	 *				array('name' => 'status', 'value' => 'New'),
	 *				array('name' => 'description', 'value' => 'I can not login to the system.'),  
	 *			    array('name' => 'first_name', 'value' => 'First Name'),
	 *			    array('name' => 'last_name', 'value' => 'Last Name'),
	 *			    array('name' => 'email1', 'value' => 'test@test.com'),	 	 
	 *			), 
	 *	 );	 
     * @return Array 'result' -- Array - Returns list fields
     */
	function set_case_record($session, $email,$name_value_list=array())
	{
		global  $current_user;
				
		if (!self::$helperObject->validate_authenticated($session)) {
			$result = array('name' => 'Invalid Session ID',
					'number' => 11,
					'description' => "The session ID is invalid");
    		return $result;
    	}

		//GET module and id if record already exists
		$get_result = $this->get_client_lead_id($session, $email);
		
		//create Lead record if not exists
		if($get_result['id'] == '')
			$get_result = $this->set_entry($session,$get_result['module'], $name_value_list, $track_view = FALSE);
		
		
		if($get_result['module'] == 'Contacts')
			$name_value_list[] = array('name' => 'contact_id', 'value' => $get_result['id']);
		else
			$name_value_list[] = array('name' => 'leads_cases_1leads_ida', 'value' => $get_result['id']);
			
		//create case record
		$caseResult = $this->set_entry($session,'Cases', $name_value_list, $track_view = FALSE);
		
		$GLOBALS['log']->info('End: SugarWebServiceImpl->set_case_record');
		
      	return $caseResult;		
	}
	
	
	 /** set_form_submission
     * Creates form submission Record and Relate with the client or Lead record as per the supplied email
	 * If there is no client/lead record found for the email address, api creates a Lead Record and associate
	 * the form submission with it. 
	 *
	 * Api checks for a Form record with  the same name, form id and domain that is passed.
	 * If not found creats a Form record and relates the form submission with the form.
     *
     * @param String $session -- Session ID returned by a previous call to login.
	 * @param String $email -- email address 
	 * @param String $form -- Form information array	 
     * @param String $name_value_list -- name value list of the data to be updated or created 
	 *
	 * Sample Array
	 * $data = array( 
	 *			'session' => $session->id, 
	 *			'email' => 'test@test.com',
	 *          'form_array' => array('name' => 'Demo Form 1234234','form_id' => '12124234dgf','domain' => 'spectrum.org'),
	 *			'name_value_list' => array( 
	 *				array('name' => 'description', 'value' => 'Form fields values'),  
	 *			    array('name' => 'first_name', 'value' => 'First Name'),
	 *			    array('name' => 'last_name', 'value' => 'Last Name'),
	 *			    array('name' => 'email1', 'value' => 'test@test.com'),	 	 
	 *			), 
	 *	 );	 
     * @return Array 'result' -- Array - Returns list fields
     */
	function set_form_submission($session, $email, $form_array=array(), $name_value_list=array())
	{
    	
		global  $current_user;
		
		if (!self::$helperObject->validate_authenticated($session)) {
			$result = array('name' => 'Invalid Session ID',
					'number' => 11,
					'description' => "The session ID is invalid");
    		return $result;
    	}		
		
		//GET OR SET FORM RECORD
		$bean = BeanFactory::getBean('Leads');
		
		$result = $bean->db->query("SELECT id,name FROM webfr_forms WHERE name='".$form_array['name']."' AND form_id='".$form_array['form_id']."' AND domain='".$form_array['domain']."' AND deleted=0");	
		if($row=$bean->db->fetchByAssoc($result)){
			$formId = $row['id'];
			$formName = $row['name'];
		}else{
			$form_name_value_list = array();
			foreach($form_array as $key=>$value){
				array_push($form_name_value_list, array('name' => $key, 'value' => $value));
			}
			$formReturn = $this->set_entry($session,'WEBFR_Forms', $form_name_value_list, $track_view = FALSE);
			$formId = $formReturn['id'];
			$formName = $form_array['name'];
		}				

		//GET MODULE AND ID IF RECORD ALREADY EXISTS
		$get_result = $this->get_client_lead_id($session, $email);
		
		//CREATE OR UPDATE LEAD RECORD
		if($get_result['id'] == ''){
			$lead_name_value_list = $name_value_list;
			$i=0;
			foreach($lead_name_value_list as $lead)
			{
				if($lead['name'] == 'description')
				unset($lead_name_value_list[$i]); //MAKE SURE WE UNSET description FIELD
				$i++;
			}		
			$get_result = $this->set_entry($session,$get_result['module'], $lead_name_value_list, $track_view = FALSE);
		}elseif($get_result['module'] == 'Leads'){
			$lead_name_value_list = $name_value_list;
			$i=0;
			foreach($lead_name_value_list as $lead)
			{
				if($lead['name'] == 'description' || $lead['name'] == 'email1') 
				unset($lead_name_value_list[$i]); //MAKE SURE WE UNSET description and email1 FIELD
				$i++;
			}			
			$lead_name_value_list[] = array('name' => 'id', 'value' => $get_result['id']);
			$this->set_entry($session,$get_result['module'], $lead_name_value_list, $track_view = FALSE);
		}
			
		//RELATE LEAD/CLIENT WITH FORM SUBMISSION
		if($get_result['module'] == 'Contacts')
			$name_value_list[] = array('name' => 'webfr_submissions_contactscontacts_ida', 'value' => $get_result['id']);
		else
			$name_value_list[] = array('name' => 'webfr_submissions_leadsleads_ida', 'value' => $get_result['id']);
		
		//RELATE FORM RECORD WITH FORM SUBMISSION	
        $name_value_list[] = array('name' => 'webfr_submissions_webfr_formswebfr_forms_ida', 'value' => $formId);
		$name_value_list[] = array('name' => 'webfr_submissions_webfr_forms_name', 'value' => $formName);			
			
		//CREATE WEB FORM SUBMITTION RECORD
		$formResult = $this->set_entry($session,'WEBFR_Submissions', $name_value_list, $track_view = FALSE);
		
		$GLOBALS['log']->info('End: SugarWebServiceImpl->set_case_record');
		
      	return $formResult;		
	}
	

	private function _get_contact_module($client_id,$bean)
	{
		$result = $bean->db->query("SELECT * FROM contacts WHERE id ='".$client_id."' AND deleted=0");
		if($row=$bean->db->fetchByAssoc($result)){
			return 'contacts';
		}else
		{
			$result = $bean->db->query("SELECT * FROM leads WHERE id ='".$client_id."' AND deleted=0");
			if($row=$bean->db->fetchByAssoc($result)){
				return 'leads';
			}else return "";	
		}
	}
			

}
