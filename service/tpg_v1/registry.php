<?php
if(!defined('sugarEntry'))define('sugarEntry', true);

/*********************************************************************************
 * REST API extesnion for handling multiple calls in one Api call
 * To handle site specific logics in one API call
 ********************************************************************************/


require_once('service/v4_1/registry.php');

class registry_tpg_v1 extends registry_v4_1 {


	/**
	 * registerFunction
     *
     * Registers all the functions on the service class
	 *
	 */
	protected function registerFunction()
	{
		$GLOBALS['log']->info('Begin: registry->registerFunction');
		parent::registerFunction();

        //get_client_lead_id returns the lead or client id for the supplied email address
        $this->serviceClass->registerFunction(
            'get_client_lead_id',
            array('session'=>'xsd:string', 'email'=>'xsd:string'),
            array('return'=>'tns:get_entry_result'));


        //set_clients_or_leads updates lead or client record as per the supplied email address
        $this->serviceClass->registerFunction(
            'set_clients_or_leads',
            array('session'=>'xsd:string','email'=>'xsd:string','name_value_list'=>'tns:name_value_list'),
            array('return'=>'tns:get_entry_result'));
			
        //set_case_record creates a case record and relates to client/lead and if not exits creats a lead.
        $this->serviceClass->registerFunction(
            'set_case_record',
            array('session'=>'xsd:string','email'=>'xsd:string','name_value_list'=>'tns:name_value_list'),
            array('return'=>'tns:get_entry_result'));
			
        //set_form_submission creates a form submission record and relates to client/lead and if not exits creats a lead.
        $this->serviceClass->registerFunction(
            'set_form_submission',
            array('session'=>'xsd:string','email'=>'xsd:string','form_array'=>'tns:form_array','name_value_list'=>'tns:name_value_list'),
            array('return'=>'tns:get_entry_result'));

	}


    /**
   	 * This method registers all the complex types
   	 *
   	 */
   	protected function registerTypes() {

           parent::registerTypes();

           $this->serviceClass->registerType
           (
               'error_value',
               'complexType',
               'struct',
               'all',
               '',
               array(
                   'number'=>array('name'=>'number', 'type'=>'xsd:string'),
                   'name'=>array('name'=>'name', 'type'=>'xsd:string'),
                   'description'=>array('name'=>'description', 'type'=>'xsd:string'),
               )
           );

            //modified_relationship_entry_list
            //This type holds the array of modified_relationship_entry types
            $this->serviceClass->registerType(
                'modified_relationship_entry_list',
                'complexType',
                'array',
                '',
                'SOAP-ENC:Array',
                array(),
                array(
                    array('ref'=>'SOAP-ENC:arrayType', 'wsdl:arrayType'=>'tns:modified_relationship_entry[]')
                ),
                'tns:modified_relationship_entry'
            );

            //modified_relationship_entry
            //This type consists of id, module_name and name_value_list type
            $this->serviceClass->registerType
            (
                 'modified_relationship_entry',
                 'complexType',
                 'struct',
                 'all',
                 '',
                 array(
                     'id' => array('name'=>'id', 'type'=>'xsd:string'),
                     'module_name' => array('name'=>'module_name', 'type'=>'xsd:string'),
                     'name_value_list' => array('name'=>'name_value_lists', 'type'=>'tns:name_value_list')
                 )
            );

            //modified_relationship_result
            //the top level result array
            $this->serviceClass->registerType
            (
                'modified_relationship_result',
                'complexType',
                'struct',
                'all',
                '',
                array(
                   'result_count' => array('name'=>'result_count', 'type'=>'xsd:int'),
                   'next_offset' => array('name'=>'next_offset', 'type'=>'xsd:int'),
                   'entry_list' => array('name'=>'entry_list', 'type'=>'tns:modified_relationship_entry_list'),
                   'error' => array('name' =>'error', 'type'=>'tns:error_value'),
                )
           );

}

}