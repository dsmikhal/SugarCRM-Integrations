<?php 

if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

/*
 * Application Class - This is a custom class used to process approved
 * 						applications that are retrieved from the LAB Group API
 * 
 * Before go live - Please test this class with all variations of application types and different types of entities, trusts etc
 * 					At time of creation we only had one application type to work with
 * 
 */
require_once('custom/CustomHandlers/Global_Functions.php');

class Applications extends Global_Functions
{
	var $strApplicationID;
	var $objApplicationMaster;
	
	// store all the beans and the relationships so that the records can be saved and created at the end
	// once all the relevant data has been parsed from the xml
	var $arrBeans;
	var $arrRelationships;
	
	var $objLead;
	var $strPrimaryContactKey;
    var $Leads = array();
	
	function __construct($objApplicationMaster)
	{
		//check the application is well formed
		if (!isset($objApplicationMaster->ApplicationID))
		{
			$strError =  "ERROR: Malformed application has no ApplicationID<br/>";
			//$GLOBALS['log']->fatal("ERROR: Malformed application has no ApplicationID");
			throw new Exception($strError);
		}
		
		$this->strApplicationID = $objApplicationMaster->ApplicationID;
		
		$this->objApplicationMaster = $objApplicationMaster;
	}
	
	function importApplication()
	{
		// check that the application ID is not already in the system
		if (empty($this->strApplicationID))
		{
			$strError = "ERROR: Could not import application as ApplicationID was missing";
			//$GLOBALS['log']->fatal($strError);
			
			throw new Exception($strError);
		}
		
		$objApplication = BeanFactory::getBean("SLCM_Applications");
		
		if ($objApplication->retrieve_by_string_fields(array('lg_application_id' => $this->strApplicationID, 'deleted' => 0)) !== null)
		{
			$strError = "ERROR: Retrieved an existing application with the same application ID " . $this->strApplicationID . " possible duplicate";
			//$GLOBALS['log']->fatal($strError);
			
			throw new Exception($strError);
		}
		
		try
		{
			$objApplication = $this->createApplication($this->objApplicationMaster);
		}
		catch (Exception $e)
		{
			throw $e;
		}
		
		
	}
	
	function createApplication()
	{
		$GLOBALS['log']->info("createApplication");
		
		$objApplication = BeanFactory::getBean("SLCM_Applications");
		$this->arrBeans['application'] = $objApplication;
        $objApplication->status = 'Disapproved';
        $GLOBALS['log']->info("createApplication: New application number ".$this->objApplicationMaster->ApplicationID);
		
		foreach ($this->objApplicationMaster as $strHeader => $objNode)
		{
			switch ($strHeader)
			{
				case "Entity":
					try
					{
						$this->createEntity($objNode);
					}
					catch (Exception $e)
					{
						throw $e;
					}
					break;
				case "Trust":
					try
					{
						$this->createTrust($objNode);
					}
					catch (Exception $e)
					{
						throw $e;
					}
					break;
				case "Addresses":
                    foreach($objNode->Address as $Addr)
                    {
                        try
                        {
                            $this->createPrimaryAddress($Addr);
                        }
                        catch (Exception $e)
                        {
                            throw $e;
                        }
                    }
					break;
				case "Individuals":
					try
					{
						$this->createIndividuals($objNode);
					}
					catch (Exception $e)
					{
						throw $e;
					}
					break;
				case "Products":
					try
					{
						$this->createProductList($objNode);
					}
					catch (Exception $e)
					{
						throw $e;
					}
					break;
				case "ApplicationID":
					$objApplication->lg_application_id = $objNode;
					$objApplication->name = $this->GenAccountNumber($objNode);
					break;
				case "DateCreated":
					$objApplication->application_created = date('Y-m-d H:i:s', strtotime(str_replace("T", " ", $objNode)));
					break;
				case "DateCompleted":
					$objApplication->application_completed = date('Y-m-d H:i:s', strtotime(str_replace("T", " ", $objNode)));
					break;
				case "ApplicationType":
					$objApplication->application_type = $objNode;
					break;
				case "ApplicationFormCode":
					$objApplication->application_form_code = $objNode;
					break;
				case "AdviserName":
					$objApplication->adviser_name = $objNode;
					break;
				case "Source":
					$objApplication->source = $objNode;
					break;
				case "Channel":
					$objApplication->channel = $objNode;
					break;
				case "PromotionCode":
					$objApplication->promotion_code = $objNode;
					break;
				case "PrimaryEmailAddress":
					$objApplication->application_email_address = $objNode;
					break;
			}
		}
		
		// Save the bean and the relationship records here:
        $QPFound = 0;
        //set Application contact email from Primary applicant
        $objApplication->app_contact_email_address_c = $this->arrBeans[$this->strPrimaryContactKey]->email1;

        //save all beans
		foreach ($this->arrBeans as $strKey => $objBean)
		{
			$GLOBALS['log']->info('createApplication: Get the bean ' . $strKey);

            //add representative
            if (isset($objBean->lg_individual_id_c) && in_array('Representative',$this->arrIndividTypes[$strKey]) && $objBean->authorised_c == 0 && $objBean->IsPrimaryContact != 1)
            {
                $this->arrNewRelationships[] = array('leftBean' => $this->arrBeans['application'],
                    'rightBean' => $objBean,
                    'relName' => 'slcm_applications_contacts_2');
            }

            //add qualified person

            if($objApplication->application_email_address == $objBean->email1 && $strKey != 'application' && $QPFound == 0)
            {
                array_unshift($this->arrNewRelationships,array('leftBean' => $objBean,
                    'rightBean' => $this->arrBeans['application'],
                    'relName' => 'contacts_slcm_applications_1'));

                /*$this->arrNewRelationships[] = array('leftBean' => $objBean,
                    'rightBean' => $this->arrBeans['application'],
                    'relName' => 'contacts_slcm_applications_1');*/
                $QPFound = 1;
            }

            if(isset($objBean->authorised_c) && ($objBean->authorised_c == 1 || in_array('Representative',$this->arrIndividTypes[$strKey])))
            {
                $objBean->save();
			    $GLOBALS['log']->info('Saved bean ID: ' . $objBean->id);
            }
            elseif(!isset($objBean->authorised_c))
            {
                $objBean->save();
                $GLOBALS['log']->info('Saved bean ID: ' . $objBean->id);
            }
		}

        //save all relationships
		foreach ($this->arrNewRelationships as $arrRelationship)
		{
			$GLOBALS['log']->info('Setting the relationship between ' . $arrRelationship['leftBean']->module_name . ' and ' . $arrRelationship['rightBean']->module_name);
			$blResult = $arrRelationship['leftBean']->load_relationship($arrRelationship['relName']);
			
			if ($blResult === false)
			{
				$strError  = "ERROR trying to LOAD the relationship " . $arrRelationship['relName'] . " between ";
				$strError .= $arrRelationship['leftBean']->module_name . " and " . $arrRelationship['rightBean']->module_name;
				
				throw new Exception($strError);
			}
			else
			{
				$blResult = $arrRelationship['leftBean']->$arrRelationship['relName']->add($arrRelationship['rightBean']->id);
				
				if ($blResult === false)
				{
					$strError  = "ERROR trying to ADD the relationship " . $arrRelationship['relName'] . " between ";
					$strError .= $arrRelationship['leftBean']->module_name . " and " . $arrRelationship['rightBean']->module_name;
					$strError .= " IDs: " . $arrRelationship['leftBean']->id . " and " . $arrRelationship['rightBean']->id;
					
					throw new Exception($strError);
				}
				else
				{
					$GLOBALS['log']->info('Succesfully set the relationship between ' . $arrRelationship['leftBean']->module_name . " and " . $arrRelationship['rightBean']->module_name);
				}
			}
		}
        $blResult = $arrRelationship['leftBean']->$arrRelationship['relName']->add($arrRelationship['rightBean']->id);
		
		// convert the lead and associate it to the contact if found:
        foreach($this->Leads as $contKey => $leadId)
        {
            //$this->Leads[$strContactKey] = $objLead->id;
            $Lead = BeanFactory::getBean("Leads",$leadId);
            if($Lead->status != 'Converted')
            {
                $Lead->status = 'Converted';
                $Lead->contact_id = $this->arrBeans[$contKey]->id;
                $Lead->converted = 1;
                $Lead->save();
            }
        }

		// send the welcome email if Application check successful:
        $CheckApp = $this->CheckApplication($this->arrBeans['application']);
        if($CheckApp['Result'])
        {
            $this->arrBeans['application']->status = "Approved";
            $this->arrBeans['application']->save();
        }
        else
        {
            $this->arrBeans['application']->description = $CheckApp['Text'];
            $this->arrBeans['application']->save();
            //Send email to the client that Application received and our managers will call you in next business day.
            $this->Send_Client_Email();
        }
	}
	

	function createPrimaryAddress($objAddress)
	{
		$objApplication = $this->arrBeans['application'];
		
		$GLOBALS['log']->info("createPrimaryAddress");

		if($objAddress->AddressType == 'MailingAddress')
        {
            foreach ($objAddress as $strHeader => $strValue)
            {
                switch ($strHeader)
                {
                    case "LineOne":
                        $objApplication->primary_address_street = $strValue;
                        break;
                    case "LineTwo":
                        $objApplication->primary_address_street .= $strValue;
                        break;
                    case "Suburb":
                        $objApplication->primary_address_city = $strValue;
                        break;
                    case "State":
                        $objApplication->primary_address_state = $strValue;
                        break;
                    case "Postcode":
                        $objApplication->primary_address_postalcode = $strValue;
                        break;
                    case "Country":
                        $objApplication->primary_address_country = $strValue;
                        break;
                }
            }
        }
	}

    function createEntityAddress($objAddress)
    {
        $objApplication = $this->arrBeans['application'];

        $GLOBALS['log']->info("createEntityAddress");

        foreach ($objAddress as $strHeader => $strValue)
        {
            switch ($strHeader)
            {
                case "LineOne":
                    $objApplication->entity_address = $strValue;
                    break;
                case "LineTwo":
                    $objApplication->entity_address .= $strValue;
                    break;
                case "Suburb":
                    $objApplication->entity_address_city = $strValue;
                    break;
                case "State":
                    $objApplication->entity_address_state = $strValue;
                    break;
                case "Postcode":
                    $objApplication->entity_address_postalcode = $strValue;
                    break;
                case "Country":
                    $objApplication->entity_address_country = $strValue;
                    break;

            }
        }
    }

    function createPhones($objPhones,$contact_id)
    {
        $objContact = $this->arrBeans[$contact_id];

        $GLOBALS['log']->info("createPhones");

        foreach ($objPhones->PhoneNumber as $Phone)
        {
            switch ($Phone->Type)
            {
                case "Home":
                    $objContact->phone_home = $Phone->AreaCode.$Phone->Number;
                    break;
                case "Work":
                    $objContact->phone_work = $Phone->AreaCode.$Phone->Number;
                    break;
                case "Mobile":
                    $objContact->phone_mobile = $Phone->AreaCode.$Phone->Number;
                    break;
                case "Fax":
                    $objContact->phone_fax = $Phone->AreaCode.$Phone->Number;
                    break;
            }
        }
    }

    function createTrustAddress($objAddress)
    {
        $objApplication = $this->arrBeans['application'];

        $GLOBALS['log']->info("createTrustAddress");

        foreach ($objAddress as $strHeader => $strValue)
        {
            switch ($strHeader)
            {
                case "LineOne":
                    $objApplication->trust_address = $strValue;
                    break;
                case "LineTwo":
                    $objApplication->trust_address .= $strValue;
                    break;
                case "Suburb":
                    $objApplication->trust_address_city = $strValue;
                    break;
                case "State":
                    $objApplication->trust_address_state = $strValue;
                    break;
                case "Postcode":
                    $objApplication->trust_address_postalcode = $strValue;
                    break;
                case "Country":
                    $objApplication->trust_address_country = $strValue;
                    break;

            }
        }
    }

    function createEntity($objEntity)
    {
        $objApplication = $this->arrBeans['application'];

        $GLOBALS['log']->info("createTrust");

        foreach ($objEntity as $strHeader => $strValue)
        {
            switch ($strHeader)
            {
                case "EntityType":
                    if ($strValue == "Company")
                    {
                        $objApplication->entity_type = 'company';
                    }
                    elseif ($strValue == "SoleTrader")
                    {
                        $objApplication->entity_type = 'sole_trader';
                    }
                    elseif ($strValue == "CorporateTrustee")
                    {
                        $objApplication->entity_type = 'corporate_trustee';
                    }
                    break;
                case "FullCompanyName":
                    $objApplication->entity_name = $strValue;
                    break;
                case "IsSMSF":
                    if ($strValue)
                    {
                        $objApplication->is_smsf = 1;
                    }
                    break;
                case "RegisteredOfficeAddress":
                    try
                    {
                        $this->createEntityAddress($strValue);
                    }
                    catch (Exception $e)
                    {
                        throw $e;
                    }
                    break;
                case "EVOverallResult":
                    $objApplication->entity_ev_overall_result = $strValue;
                    break;
                case "TaxationDetails":
                    $objApplication->entity_abn = $strValue->ABN;
                    break;
            }
        }
    }

    function createTrust($objTrust)
    {
        $objApplication = $this->arrBeans['application'];

        $GLOBALS['log']->info("createTrust");

        foreach ($objTrust as $strHeader => $strValue)
        {
            switch ($strHeader)
            {
                case "TrustType":
                    if ($strValue == "�Registered Managed Investment Schem")
                    {
                        $objApplication->trust_type = 'registered_managed_investment_scheme';
                    }
                    elseif ($strValue == "Regulated Trust" || $strValue == "Regulated Trust (eg. Self Managed Super Fund)")
                    {
                        $objApplication->trust_type = 'regulated_trust';
                    }
                    elseif ($strValue == "Government Superannuation Fund")
                    {
                        $objApplication->trust_type = 'government_superannuation_fund';
                    }
                    elseif ($strValue == "Other Trust Type" || $strValue == "Other Trust Type (eg. Family, Unit)")
                    {
                        $objApplication->trust_type = 'other_trust_type';
                    }
                    break;
                case "IsSMSF":
                    if ($strValue)
                    {
                        $objApplication->is_smsf = 1;
                    }
                    break;
                case "BusinessAddress":
                    try
                    {
                        $this->createTrustAddress($strValue);
                    }
                    catch (Exception $e)
                    {
                        throw $e;
                    }
                    break;
                case "EVOverallResult":
                    $objApplication->trust_ev_overall_result = $strValue;
                    break;
                case "FullTrustName":
                    $objApplication->trust_name = $strValue;
                    break;
                case "TaxationDetails":
                    $objApplication->trust_abn = $strValue->ABN;
                    $objApplication->trust_tfn_c = $strValue->TaxFileNumber;
                    break;
            }
        }
    }
	
	function createIndividuals($objIndividuals)
	{
		$intIndividuals = 0;
                $objIndividList = $objIndividuals->Individual;

		foreach ($objIndividList as $objIndividual)
		{
			$intIndividuals++;

			// create a contact object
			$objContact = BeanFactory::getBean("Contacts");
			$strContactKey = "contacts_" . $intIndividuals;
			$strEmailAddress = "";
			$blPrimaryContact = 0;
			
			$this->arrBeans[$strContactKey] = $objContact;
			
			if (isset($objIndividual->EmailAddress))
			{
				$strEmailAddress = $objIndividual->EmailAddress;
			}
            $not_update = 0;
			if (isset($objIndividual->EmailAddress) && !empty($objIndividual->EmailAddress) && !empty($strEmailAddress))
			{
				$strEmailAddress = $objIndividual->EmailAddress;
				
				// search for email address in contacts
				$objExistingContact = $this->searchExistingEmail($strEmailAddress);
                $create_new = 0;

				// update existing contacts detail if contact is found
				if ($objExistingContact == false)
				{ // do nothing
                }
				else
				{
                    if(strtoupper($objExistingContact->first_name) == strtoupper($objIndividual->Name->FirstName) && strtoupper($objExistingContact->last_name) == strtoupper($objIndividual->Name->Surname))
                    {
                        $this->arrBeans[$strContactKey] = $objExistingContact;
                        $objContact = $objExistingContact;
                        $not_update = 1;
                    }
                    else
                    {$create_new = 1;}

				}
			}
			
			if (isset($objIndividual->IsPrimaryContact) && $objIndividual->IsPrimaryContact)
			{
				$blPrimaryContact = 1;
				$this->strPrimaryContactKey = $strContactKey;
            }
				
            if($not_update == 0)
            {
			foreach ($objIndividual as $strHeader => $strValue)
			{
				switch ($strHeader)
				{
					case "IndividualID":
						$objContact->lg_individual_id_c = $strValue;
						break;
                    case "Name":
                        $objContact->salutation = $strValue->Title . ".";
                        $objContact->first_name = $strValue->FirstName;
                        $objContact->last_name = $strValue->Surname;
                        break;
					/*case "Title":
						$objContact->salutation = $strValue . ".";
						break;
					case "FirstName":
						$objContact->first_name = $strValue;
						break;
					case "Surname":
						$objContact->last_name = $strValue;
						break;*/
                    case "DateOfBirth":
                        $objContact->birthdate = date('Y-m-d',strtotime($strValue));
                        break;
                    case "EmailAddress":
                        if($create_new == 0)
                            {$objContact->email1 = $strValue;}
                        elseif($create_new == 1)
                            {$objContact->email1 = "duplicate_".$strValue;
                             $objContact->description = "Duplicate email: ".$strValue;}
                       // $objContact->email1 = "d.mikhalchenko@tradingpursuits.com";
                        break;
                    case "DriversLicenseNumber":
                        $objContact->driver_license_c = $strValue;
                        break;

                    case "PhoneNumbers":
                        try
                        {
                            $this->createPhones($strValue,$strContactKey);
                        }
                        catch (Exception $e)
                        {
                            throw $e;
                        }
                        break;
                    case "Addresses":
                        foreach($strValue->Address as $Address)
                        {
                            if($Address->AddressType == 'IndividualResidentialAddress')
                            {
                                $objContact->primary_address_country = $Address->Country;
                                $objContact->primary_address_street = $Address->LineOne." ".$Address->LineTwo;
                                $objContact->primary_address_city = $Address->Suburb;
                                $objContact->primary_address_state = $Address->State;
                                $objContact->primary_address_postalcode = $Address->Postcode;
                            }
                        }
                        break;
                    case "TaxationDetails":
                        if(empty($objContact->tfn_c) && !empty($strValue->TaxFileNumber))
                            {$objContact->tfn_c = $strValue->TaxFileNumber;}
                        break;
					case "RoleInCompany":
						$objContact->title = $strValue;
						break;
					case "IsAuthorisedSignatory":
						$objContact->authorised_c = $strValue;
						break;
                    case "IndividualTypes":
                        try
                        {
                            $this->createTypes($strValue,$strContactKey);
                        }
                        catch (Exception $e)
                        {
                            throw $e;
                        }
                        break;

				}
			}
            }
            else
            {
                if(empty($objContact->tfn_c))
                   {$objContact->tfn_c = $objIndividual->TaxationDetails->TaxFileNumber;}

				//$objContact->lg_individual_id_c = $objIndividual->IndividualID;
            }
			// set the relationships between the contacts and the application
			if ($blPrimaryContact)
			{
				$this->arrNewRelationships[] = array('leftBean' => $this->arrBeans['application'],
													'rightBean' => $objContact,
													'relName' => 'slcm_applications_contacts_1');
			}
			elseif($objContact->authorised_c == 1)
			{
				$this->arrNewRelationships[] = array('leftBean' => $this->arrBeans['application'],
													'rightBean' => $objContact,
													'relName' => 'slcm_applications_contacts');
			}

            //Find a Lead for each new client by email and Last_name. First name optional.
            if (!empty($strEmailAddress))
            {
                // search for the email address in the leads module
                $objLead = $this->searchExistingEmail($strEmailAddress, "Leads");

                // set the lead object so that it can be converted after contact creation
                if ($objLead !== false && $objLead->status != 'Converted')
                {
                    if(strtoupper($objLead->last_name) == strtoupper($objContact->last_name))
                    {
                        $this->Leads[$strContactKey] = $objLead->id;
                        $objContact->full_lead_match_c = 1;
                        //if($blPrimaryContact) $this->objLead = $objLead;

                        if(empty($objContact->client_class_c) || strlen($objContact->client_class_c) < strlen($objLead->client_class_c))
                            $objContact->client_class_c = $objLead->client_class_c;

                        if(empty($objContact->tp_account_code_c))
                            $objContact->tp_account_code_c = $objLead->tp_account_code_c;

                        if(strtoupper($objLead->first_name) == strtoupper($objContact->first_name))
                            $objContact->full_lead_match_c = 0;

                    }
                }
            }
			
		}
	}

    function GenAccountNumber($AppID)
    {

        $AppIDarr = str_split($AppID);
        $AppIDarrRev = array_reverse($AppIDarr);

        $j=0;
        for($i=0;$i<strlen($AppID);$i++)
        {
            if($j==0)
            {
                $AppIDarrRev[$i] = $AppIDarrRev[$i]*2;
                $AppIDarrRev[$i] = array_sum(str_split($AppIDarrRev[$i]));
                $j=1;
            }
            elseif($j==1)
            { $j=0; }
        }

        $PreSpinner = array_sum($AppIDarrRev)%10;
        If($PreSpinner==0)
        { $spiner=$PreSpinner;}
        else
        {$spiner = 10 - $PreSpinner;}
        $accnum = $AppID.$spiner;

        return $accnum;

    }

	function createProductList($objProducts)
	{
        $intProducts = 0;

        $objProductList = $objProducts->Product;

        foreach($objProductList as $Portf)
        {
            $ProductCat = BeanFactory::getBean("ProductTemplates");
            $SQL = $ProductCat->db->query("SELECT pt.name, pt.id,pc.name as category, pc.id as cat_id FROM product_templates pt, product_categories pc
                        WHERE pt.category_id=pc.id and instr('{$Portf->ProductName}',pt.description)>0 and pt.name not like 'ForexPropel%'");

            if($row = $ProductCat->db->fetchByAssoc($SQL))
            {
                $intProducts++;

                $objProduct = BeanFactory::getBean("Products");
                $strProductKey = "products_" . $intProducts;

                $this->arrBeans[$strProductKey] = $objProduct;

                $objProduct->name = $row['name'];
                $objProduct->product_template_id = $row['id'];
                $objProduct->status = 'Registered';
                $objProduct->category_name = $row['category'];
                $objProduct->category_id = $row['cat_id'];
                $objProduct->date_purchased = date('Y-m-d');

                $this->arrNewRelationships[] = array('leftBean'  => $this->arrBeans['application'],
                                                     'rightBean' => $objProduct,
                                                     'relName'   => 'slcm_applications_products_1');
            }
        }

	}
	
	function createTypes($objIndividualTypes,$strContactKey)
	{
        $IndividType = array();
        foreach($objIndividualTypes->EIndividualType as $IndividualType)
        {
            $IndividType[] = $IndividualType;
        }
        $this->arrIndividTypes[$strContactKey] =  $IndividType;

	}

   	/**
	 * 
	 * searchExistingEmail - Search for an existing record in a module with that email address.
	 * 							Returns the object if a match is found
	 * @param $strEmailAddress - email address to search for
	 * @param $strModule - defaults to Contacts to search in
	 */
	function searchExistingEmail($strEmailAddress = "", $strModule = "Contacts")
	{
		$GLOBALS['log']->info("function::searchExistingContacts(emailaddress:$strEmailAddress)");
		
		if ($strEmailAddress != "")
		{
			$objSearchContact = BeanFactory::getBean($strModule);
			
			$strEmailAddress = trim($strEmailAddress);
			$arrContacts = $objSearchContact->emailAddress->getRelatedId($objSearchContact->db->quote($strEmailAddress), $strModule);
			
			//$GLOBALS['log']->info("arrContacts from email address: " . print_r($arrContacts, true));
			
			if ($arrContacts !== false)
			{
				$objContact = BeanFactory::getBean($strModule);
				$objContact->retrieve($arrContacts[0]);
				
				$GLOBALS['log']->info("Exisiting $strModule record Sugar ID from email address: " . $objContact->id);
				
				return $objContact;
			}
		}
		return false;
	}
	
    function Send_Client_Email()
    {
        if($this->arrBeans['contacts_1']->email1 != '')
        {
            $emailTemplate =  'xxxx-deddd-ggggg-eeee';

            //GET EMAIL TEMPALTE FOR INVOICE
            $emailTemp = BeanFactory::getBean('EmailTemplates');
            $emailTemp->retrieve($emailTemplate);

            $emailObj = new Email();
            $defaults = $emailObj->getSystemDefaultEmail();

            //SET TO ADDRESS
            $toAddresses = array();
            if(!empty($this->arrBeans['application']->app_contact_email_address_c))
                $toAddresses[$this->arrBeans['contacts_1']->first_name." ".$this->arrBeans['contacts_1']->last_name] = $this->arrBeans['application']->app_contact_email_address_c;
            else
                $toAddresses[$this->arrBeans['contacts_1']->first_name." ".$this->arrBeans['contacts_1']->last_name] = $this->arrBeans['contacts_1']->email1;

            //REPLACE VALUES FOR KEYS IN EMAIL TEMPLATE
            $data1 = $this->GetLongName($this->arrBeans['application'],$this->arrBeans['contacts_1']);
            $data = array($this->arrBeans['contacts_1']->first_name,$this->arrBeans['application']->name,$this->arrBeans['application']->application_type,$data1['LongName']);
            $keys = array('$contact_first_name','$acc_num','$acc_type','$acc_name');

            $body = str_replace($keys,$data,$emailTemp->body_html);

            //SEND EMAIL
            $this->_Send_Email($defaults['email'],$defaults['name'],$toAddresses,$emailTemp->subject,'SLCM_Applications',$this->arrBeans['application']->id,$body,array(),true);
            $GLOBALS['log']->info("Email to fund account has been sent. Client: ".$this->arrBeans['contacts_1']->first_name." ".$this->arrBeans['contacts_1']->last_name);
        }
        else
        {$GLOBALS['log']->error("Email to fund account has not been sent: email is empty! Client: ".$this->arrBeans['contacts_1']->first_name." ".$this->arrBeans['contacts_1']->last_name);}

    }
}


?>
