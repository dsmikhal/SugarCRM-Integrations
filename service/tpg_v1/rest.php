<?php
 if(!defined('sugarEntry'))define('sugarEntry', true);
/*********************************************************************************
 * REST API extesnion for handling multiple calls in one Api call
 * To handle site specific logics in one API call
 ********************************************************************************/


/**
 * This is a rest entry point for rest version TP V1
 */
chdir('../..');
require_once('SugarWebServiceImpltpg_v1.php');
$webservice_class = 'SugarRestService';
$webservice_path = 'service/core/SugarRestService.php';
$webservice_impl_class = 'SugarWebServiceImpltpg_v1';
$registry_class = 'registry';
$location = '/service/tpg_v1/rest.php';
$registry_path = 'service/tpg_v1/registry.php';
require_once('service/core/webservice.php');
