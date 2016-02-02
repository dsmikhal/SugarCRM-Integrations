<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*
 * Copyright (C) Dmitrii. All rights reserved.
 */

require_once('data/BeanFactory.php');
require_once('custom/CustomHandlers/Global_Functions.php');

class SendInvoiceApi extends SugarApi
{
    // This function is only called whenever the rest service cache file is deleted.
    // This shoud return an array of arrays that define how different paths map to different functions
    public function registerApiRest() {
        return array(
            'sendInvoice' => array(
                // What type of HTTP request to match against, we support GET/PUT/POST/DELETE
                'reqType' => 'GET',
                // This is the path you are hoping to match, it also accepts wildcards of ? and <module>
                'path' => array('TRANN_Trans','Send_Invoice'),
                // These take elements from the path and use them to populate $args
                'pathVars' => array('', ''),
                // This is the method name in this class that the url maps to
                'method' => 'sendInvoice',
                // The shortHelp is vital, without it you will not see your endpoint in the /help
                'shortHelp' => 'Send PDF Invoice to Customer',
                // The longHelp points to an HTML file and will be there on /help for people to expand and show
                'longHelp' => '',
            ),
        );
    }

    function sendInvoice($api, $args)
    {
        $global = new Global_Functions();

        //$GLOBALS['log']->error("PT_Dispatch: Args: ".print_r($args,true));
        if(isset($args['invoice']) && !empty($args['invoice'])){
            $InvObj = BeanFactory::getBean('TRANN_Trans',$args['invoice']);

            if($InvObj->trans_type == 'Invoice')
                require_once("custom/CustomHandlers/Invoice_PDF.php");
            elseif($InvObj->trans_type == 'CreditMemo')
                require_once("custom/CustomHandlers/CreditMemo_PDF.php");

            $InvObj->load_relationship('contacts_trann_trans_1');
            $clientObj = current($InvObj->contacts_trann_trans_1->getBeans());
            // if(!empty($clients))                $clientObj = current($clients);
        }
        else return false;



/*        $clientId = $global->_get_related_id($this->bean,'contacts_trann_trans_1contacts_ida','contacts_trann_trans_1');
        $clientObj = BeanFactory::getBean('Contacts');
        $clientObj->retrieve($clientId);*/

        if($InvObj->trans_type == 'Invoice')
            $PDFObj = new Invoice();
        elseif($InvObj->trans_type == 'CreditMemo')
            $PDFObj= new CreditMemo();

        //GET INVOICE PRODUCT ITEMS AND PRICE
        $items = array();
        for($k=1;$k<5;$k++)
        {
            $itemID = 'trann_items_id'.$k.'_c';
            $price = 'amount_'.$k;
            if($InvObj->$itemID != '' && $InvObj->$price != 0)
            {
                $itemObj = BeanFactory::getBean('TRANN_Items',$InvObj->$itemID);

                $taxObj = BeanFactory::getBean('TAXRA_Tax',$itemObj->taxra_tax_id_c);

                $items[$k]['name'] = $itemObj->display_name;
                $items[$k]['price'] = number_format($InvObj->$price,2);

                $amount_net = round((float) $InvObj->$price / (1 + (float) $taxObj->rate / 100), 2);
                $amount_tax = round((float) $InvObj->$price - (float) $amount_net, 2);

                $items[$k]['amount_net'] = number_format($amount_net,2);
                $items[$k]['amount_tax'] = number_format($amount_tax,2);

            }
        }


        //GET PAYMENTS FOR THIS INVOICE
        $query = "SELECT m.name as method,pay.* FROM trann_trans_trann_payments_1_c as link, trann_payments as pay,trann_payment_method as m WHERE link.trann_trans_trann_payments_1trann_trans_ida = '".$InvObj->id."' AND link.deleted=0 AND pay.id=link.trann_trans_trann_payments_1trann_payments_idb AND pay.deleted=0 AND m.id=pay.trann_payment_method_id_c AND m.deleted=0 ORDER BY pay.date_entered DESC";

        $result = $InvObj->db->query($query,true);
        $payments = array();
        $j=0;
        while($row=$InvObj->db->fetchByAssoc($result)){
            $payments[$j]['number'] = $row['name'];
            $payments[$j]['method'] = $row['method'];
            $payments[$j]['date'] = $row['payment_date'];
            if($row['trann_trans_id1_c'] == $InvObj->id)
                $payments[$j]['amount'] = number_format($row['amount_applied_1'],2);
            if($row['trann_trans_id2_c'] == $InvObj->id)
                $payments[$j]['amount'] = number_format($row['amount_applied_2'],2);
            if($row['trann_trans_id3_c'] == $InvObj->id)
                $payments[$j]['amount'] = number_format($row['amount_applied_3'],2);
            $j++;
        }

        $InvObj->amount_remaining = number_format($InvObj->amount_remaining,2);
        $InvObj->amount_total = number_format($InvObj->amount_total,2);

        //CREATE INVOICE
        $PDFObj->Generate_PDF($InvObj,$clientObj,$payments,$items);

        //GET EMAIL TEMPALTE FOR INVOICE
        $emailTemp = BeanFactory::getBean('EmailTemplates');
        if($InvObj->trans_type == 'Invoice')
            $emailTemp->retrieve($global->INVOICE_TEMPLATE);
        elseif($InvObj->trans_type == 'CreditMemo')
            $emailTemp->retrieve($global->CREDIT_MEMO_TEMPLATE);
        $emailObj = new Email();
        $defaults = $emailObj->getSystemDefaultEmail();

        //SET TO ADDRESSES
        $toAddresses = array();
        $toAddresses[$clientObj->first_name.' '.$clientObj->last_name] = $clientObj->email1;
        if($InvObj->email_address != '')
        {
            $extraEmails = explode(',',$InvObj->email_address);

            foreach($extraEmails as $email){
                $toAddresses[$email] = $email;

            }
        }

        //REPLACE VALUES FOR KEYS IN EMAIL TEMPLATE
        $data = array($clientObj->first_name.' '.$clientObj->last_name,$clientObj->first_name,$items[1]['name']);
        $keys = array('$contact_name','$contact_first_name','$product');

        $body = str_replace($keys,$data,$emailTemp->body_html);
        $subject = str_replace($keys,$data,$emailTemp->subject);


        //ATTACH INVOICE PDF
        $attachedFiles =array(array('file_location'=> "cache/invoices/",'filename'=>$InvObj->name.".pdf"));

        //SEND EMAIL
        if(!$global->_Send_Email($defaults['email'],$defaults['name'],$toAddresses,$subject.' - '.$InvObj->name,'Contacts',$clientObj->id,$body,$attachedFiles,true))
            return false;
        else
            return true;
    }
}