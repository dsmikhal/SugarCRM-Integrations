<?php 

if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

/*
 * GetCompletedApplications - This script will be scheduled in cron to every 15 minutes to
 *                            retrieve data from the Lab Group API.
 *                            
 * 
 */


//for testing:

/*
if(!defined('sugarEntry'))
define('sugarEntry', true);
chdir("../../../");
require_once('include/entryPoint.php');
require_once('data/SugarBean.php');
require_once('modules/Contacts/Contact.php');

*/

ini_set("soap.wsdl_cache_enabled", "0"); 

class AuthHeader
{
	var $User;//string
 	var $Password;//string
 	
 	function __construct($arrAuth)
 	{
 		$this->User = $arrAuth['User'];
 		$this->Password = $arrAuth['Password'];
 	}
}


// Custom Soap class for Testing
class MSSoapClient extends SoapClient
{

	function __doRequest($request, $location, $action, $version)
	{
		$namespace = "https://webservice.eappform.com/eappformfunctions.asmx";
		
		/*
		//TESTING
		echo "REQUEST BEFORE:<br/>";
		echo $request."<br/><br/>";
		*/
		
		$request = preg_replace('/<ns1:(\w+)/', '<$1 xmlns="'.$namespace.'"', $request, 1);
		$request = preg_replace('/<ns1:(\w+)/', '<$1', $request);
		$request = str_replace(array('/ns1:', 'xmlns:ns1="'.$namespace.'"', 'SOAP-ENV', 'xsd:', '<AuthHeader','env:' ,':env'), array('/', '', 'soap', '', "<AuthHeader xmlns=\"$namespace\"", 'soap:',':soap'), $request);
		
		/*
		//TESTING
		echo "REQUEST AFTER:<br/>";
		echo $request."<br/><br/>";
		*/
		
		return parent::__doRequest($request, $location, $action, $version);
	}
}


Function GetApplication()
{
    global $sugar_config;

    if(isset($sugar_config['labgroup_id']) && $sugar_config['labgroup_id'] != "")
    {
        // HTML Authorization
        $arrSoapOptions = array(
            'trace' => true,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
            'login'    => $sugar_config['labgroup_user'],
            'password' => $sugar_config['labgroup_pass']
        );

        // User Authorization
        $arrAuth = 	array (	'User' => $sugar_config['labgroup_user'],
            'Password' => $sugar_config['labgroup_pass']
        );

        $strWSDL = $sugar_config['labgroup_url'];
        $objSoapClient = new SoapClient($strWSDL, $arrSoapOptions);
        $objAuthHeader = new AuthHeader($arrAuth);

        $objHeader = new SoapHeader(XSD_NAMESPACE, 'AuthHeader', $objAuthHeader, false);
        $objSoapClient->__setSoapHeaders($objHeader);

        $objResult = $objSoapClient->GetSingleApplication(array("applicationID" => $sugar_config['labgroup_id']));
        if (is_soap_fault($objResult))
        {
            $strError = "SOAP Fault: (faultcode: {$objResult->faultcode} faultstring: {$objResult->faultstring})";
            $GLOBALS['log']->fatal($strError);
            trigger_error ("SOAP Fault: (faultcode: {$objResult->faultcode}, faultstring: {$objResult->faultstring})", E_USER_ERROR);
        }

        require_once("LabGroup/AccountApplications/classes/class.Applications.php");

        if (isset($objResult->GetSingleApplicationResult) && count($objResult->GetSingleApplicationResult->Applications->Application) > 0)
        {
            $ApplicationResult = $objResult->GetSingleApplicationResult->Applications;
            $objApplication = new Applications(current($ApplicationResult->Application));
            try
            {
                $objApplication->importApplication();
            }
            catch (Exception $e)
            {
                $GLOBALS['log']->fatal($e->getMessage());
            }
        }
        else
        {
            $strError = "No application with id = ".$sugar_config['labgroup_id']." was returned by the API";
            $GLOBALS['log']->fatal($strError);
            trigger_error ($strError, E_USER_ERROR);
        }

    }
    return true;
}

function GetCompletedApplications()
{
    global $sugar_config;
	$arrSoapOptions = array(
        'trace' => true,
        'exceptions' => true,
        'cache_wsdl' => WSDL_CACHE_NONE,
        'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
        'login'    => $sugar_config['labgroup_user'],
        'password' => $sugar_config['labgroup_pass']
		);
	
	$arrAuth = 	array (	'User' => $sugar_config['labgroup_user'],
						'Password' => $sugar_config['labgroup_pass']
                      );

    $strWSDL = $sugar_config['labgroup_url'];

	$objSoapClient = new SoapClient($strWSDL, $arrSoapOptions);

	$objAuthHeader = new AuthHeader($arrAuth);
	
	$objHeader = new SoapHeader(XSD_NAMESPACE, 'AuthHeader', $objAuthHeader, false);
	$objSoapClient->__setSoapHeaders($objHeader);

	$objResult = $objSoapClient->GetApplications(array("lastApplicationID" => "-1", "maximumApplicationCount" => 3));
	
	if (is_soap_fault($objResult))
	{
		$strError = "SOAP Fault: (faultcode: {$objResult->faultcode} faultstring: {$objResult->faultstring})";
		$GLOBALS['log']->fatal($strError);
    	trigger_error ("SOAP Fault: (faultcode: {$objResult->faultcode}, faultstring: {$objResult->faultstring})", E_USER_ERROR);
	}
	
	/*
	//TESTING
	echo "<pre>";
	print_r($objResult);
	echo "</pre>";
	*/

    require_once("LabGroup/AccountApplications/classes/class.Applications.php");

    if (isset($objResult->GetApplicationsResult) && count($objResult->GetApplicationsResult->Applications->Application) > 0)
    {
        $objApplicationsList = $objResult->GetApplicationsResult->Applications;

        foreach ($objApplicationsList->Application as $objApplication)
        {
            $objApplication = new Applications($objApplication);
            try
            {
                $objApplication->importApplication();
            }
            catch (Exception $e)
            {
                $GLOBALS['log']->fatal($e->getMessage());
            }
            unset($objApplication);
        }

    }
    else
    {
        $strError = "No result list of applications was returned by the API or List is empty";
        $GLOBALS['log']->error($strError);
        trigger_error ($strError, E_USER_NOTICE);
    }
	
	// For the SugarCRM scheduler to complete the task, this function must return true
	return true;

}


?>