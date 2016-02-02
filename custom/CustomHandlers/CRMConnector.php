<?php
/**
 * Created by PhpStorm.
 * User: d.mikhalchenko
 * Date: 14/02/14
 * Time: 11:05 AM
 */

Class SugarCRMConnector
{
    var $SESSION = '';
    var $url;
    var $user;
    var $pass;
    var $schema;

    Function __construct($crm)
    {
        $this->url  = $crm['url'];
        $this->user = $crm['user'];
        $this->pass = $crm['pass'];
        $this->schema = $crm['schema'];
    }

    function call($method, $parameters, $url)
    {

        ob_start();
        $curl_request = curl_init();
        curl_setopt($curl_request, CURLOPT_URL, $url);
        curl_setopt($curl_request, CURLOPT_POST, 1);
        curl_setopt($curl_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($curl_request, CURLOPT_HEADER, 1);
        curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, 0);

        $jsonEncodedData = json_encode($parameters);

        $post = array(
             "method" => $method,
             "input_type" => "JSON",
             "response_type" => "JSON",
             "rest_data" => $jsonEncodedData
        );
        curl_setopt($curl_request, CURLOPT_POSTFIELDS, $post);
        $result = curl_exec($curl_request);
        curl_close($curl_request);
        $result = explode("\r\n\r\n", $result, 2);
        $response = json_decode($result[1]);
        ob_end_flush();
        return $response;
    }


    Function Login()
    {
        $login_parameters = array(
             "user_auth"=>array(
                  "user_name"=>$this->user,
                  "password"=>$this->pass,
                  "version"=>"1",
             ),
             "application_name"=>"RestTest",
             "name_value_list"=>array(),
        );
        $login_result = $this->call("login", $login_parameters, $this->url);
        // Save the session id
        if(!empty($login_result->id))
        {
            $this->SESSION = $login_result->id;
            $GLOBALS['log']->info("CRM Session_ID has been received.");
            return true;

        }
        else
        {
            $GLOBALS['log']->error("CRM Session_ID has not been received.");
            return false;
        }

    }

    Function CreateUpdateClient($data,$dtr_client_id = null)
    {
        if(!empty($this->SESSION))
        {
            if(empty($dtr_client_id))
            {
              $set_entry_parameters = array(
                //session id
                "session" => $this->SESSION,
                //The name of the module
                "module_name" => "Contacts",
                //Record attributes
                "name_value_list" => array(
                    //to update a record, you will nee to pass in a record id as commented below
                    //array("name" => "id", "value" => "9b170af9-3080-e22b-fbc1-4fea74def88f"),
                    array("name" => "first_name", "value" => $data->first_name),
                    array("name" => "last_name", "value" => $data->last_name),
                    array("name" => "phone_home", "value" => $data->phone_home),
                    array("name" => "phone_mobile", "value" => $data->phone_mobile),
                    array("name" => "primary_address_city", "value" => $data->primary_address_city),
                    array("name" => "primary_address_country", "value" => $data->primary_address_country),
                    array("name" => "primary_address_postalcode", "value" => $data->primary_address_postalcode),
                    array("name" => "primary_address_state", "value" => $data->primary_address_state),
                    array("name" => "primary_address_street", "value" => $data->primary_address_street),
                    array("name" => "email1", "value" => $data->email1),
                    array("name" => "assigned_user_id", "value" => $data->assigned_user_id),
                    array("name" => "tp_account_code_c", "value" => $data->account_code_c),
                    array("name" => "description", "value" => "Has been created from TP managed Subscription.")
                )
              );
            }
            else
            {
                $set_entry_parameters = array(
                    //session id
                    "session" => $this->SESSION,
                    //The name of the module
                    "module_name" => "Contacts",
                    //Record attributes
                    "name_value_list" => array(
                        //to update a record, you will nee to pass in a record id as commented below
                        array("name" => "id", "value" => $dtr_client_id),
                        array("name" => "tp_account_code_c", "value" => $data->account_code_c),
                    )
                );
            }

             $set_entry_result = $this->call("set_entry", $set_entry_parameters, $this->url);
            $GLOBALS['log']->info("CreateUpdateClient: Client with id = {$set_entry_result->id} updated. REquest: ".print_r($set_entry_parameters,true)." Response: ".print_r($set_entry_result,true));
            return $set_entry_result->id;
        }
        else
        {
            $GLOBALS['log']->error("CRM Session has not been established.");
            return false;
        }
    }

    Function CreateUpdateMembership($data = array())
    {
        if(!empty($this->SESSION))
        {
            //if(empty($data['id']))          {
            $set_entry_parameters = array(
                //session id
                "session" => $this->SESSION,
                //The name of the module
                "module_name" => "MEMS_membership",
                //Record attributes
                "name_value_list" => array(
                ),
            );
            foreach($data as $key => $value)
                array_push( $set_entry_parameters["name_value_list"],array("name" => $key, "value" => $value));

            $set_entry_result = $this->call("set_entry", $set_entry_parameters, $this->url);
            $GLOBALS['log']->info("CreateUpdateMembership: Membership with id = {$set_entry_result->id} updated/created.");
            return $set_entry_result->id;
        }
        else
        {
            $GLOBALS['log']->error("CRM Session has not been established.");
            return false;
        }
    }

    Function FindUserByEmail($last_name,$email)
    {
        $sql = $GLOBALS['db']->query("select c.* from {$this->schema}contacts c
              INNER JOIN  {$this->schema}email_addr_bean_rel l4_1 ON c.id=l4_1.bean_id AND l4_1.deleted=0 AND l4_1.primary_address = '1'
              INNER JOIN  {$this->schema}email_addresses l4 ON l4.id=l4_1.email_address_id AND l4.deleted=0
                where c.last_name = '{$last_name}' and l4.email_address = '{$email}' and c.deleted = 0");

        if($row = $GLOBALS['db']->fetchByAssoc($sql))
        {
            $GLOBALS['log']->info("FindUserByEmail: Client with email {$email} found with id = {$row['id']}.");
            return $row['id'];
        }
        else
        {
            $GLOBALS['log']->info("FindUserByEmail: Client with email {$email}  not found.");
            return false;
        }
    }

    Function FindUserByPPnumber($account_code)
    {
        $sql = $GLOBALS['db']->query("select c.* from {$this->schema}contacts c
              INNER JOIN  {$this->schema}contacts_cstm cc ON c.id=cc.id_c AND c.deleted=0 where cc.tp_account_code_c = '{$account_code}'");

        if($row = $GLOBALS['db']->fetchByAssoc($sql))
        {
            $GLOBALS['log']->info("FindUserByPPnumber: Client with code {$account_code} found with id = {$row['id']}.");
            return $row['id'];
        }
        else
        {
            $GLOBALS['log']->info("FindUserByPPnumber: Client with code {$account_code} NOT found.");
            return false;
        }
    }

    Function FindActiveSubscription($client_id)
    {
        $sql = $GLOBALS['db']->query("SELECT m.id, m.name,ti.name as product, ti.product_class, ti.id as product_id, DATE_FORMAT(m.start_date,'%Y-%m-%d') as start_date, DATE_FORMAT(m.next_billing_date,'%Y-%m-%d') as next_billing_date,
          m.status ,DATE_FORMAT(m.expirydate,'%Y-%m-%d') as expirydate FROM {$this->schema}mems_membership m
          inner join {$this->schema}contacts_mems_membership_1_c mc ON m.id=mc.contacts_mems_membership_1mems_membership_idb AND mc.deleted=0
          INNER JOIN {$this->schema}trann_items ti ON m.trann_items_id_c = ti.id
          WHERE m.deleted = 0 AND m.status NOT IN ('Canceled', 'Expired') AND mc.contacts_mems_membership_1contacts_ida = '{$client_id}' and ti.item_class != 'Product'");

        if($row = $GLOBALS['db']->fetchByAssoc($sql))
        {
            $GLOBALS['log']->info("FindActiveSubscription: Active subscription found  {$row['name']} {$row['product']}.");
            return $row;
        }
        else
        {
            $GLOBALS['log']->info("FindActiveSubscription: Active subscription NOT found  for client {$client_id}.");
            return false;
        }
    }


}