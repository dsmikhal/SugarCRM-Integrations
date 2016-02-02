<?php

require_once('custom/CustomHandlers/Global_Functions.php');

class TRANN_TranHandler extends Global_Functions {
	

	function RCNUM_Record_Numbering($bean, $event, $arguments) 
	{

		require_once('custom/CustomHandlers/RCNUM_Record_Numbering/RCNUM_Record_NumberingHandler.php');
			
        if (empty($bean->fetched_row)) {
            // set number depending on the transaction type
            switch ($bean->trans_type) {
                case 'CreditMemo':
                    $rcnum = 'trann_trans_cm';
                    break;
                case 'CashClaim':
                    $rcnum = 'trann_trans_bill';
                    break;
                case 'WebInvoice':
                    $rcnum = 'trann_trans_def';
                    $bean->trans_type = 'Invoice';
                    break;					
                case 'Invoice':
                default:
                    $rcnum = 'trann_trans_inv';
                    break;
            }
			
            // set number depending on the transaction type
            if($bean->trans_type == 'Invoice' && $bean->company == 'TPO') {
                    $rcnum = 'trann_trans_def';
            }			
            RecordNumbering::ittap_rcnum($rcnum, $bean);
        }
		
	}


	function Create_Invoice_Pdf($bean, $event, $arguments)
	{
		if(($bean->trans_type == 'CreditMemo' || $bean->trans_type == 'Invoice') && /*!empty($bean->fetched_row) && $bean->amount_remaining == 0 &&*/ $bean->send_invoice_email == 1)
		{
			if($bean->trans_type == 'Invoice')
				require_once("custom/CustomHandlers/Invoice_PDF.php");
			elseif($bean->trans_type == 'CreditMemo')
				require_once("custom/CustomHandlers/CreditMemo_PDF.php");

			$clientId = $this->_get_related_id($bean,'contacts_trann_trans_1contacts_ida','contacts_trann_trans_1');
			$clientObj = BeanFactory::getBean('Contacts');
			$clientObj->retrieve($clientId);

			if($bean->trans_type == 'Invoice')
				$PDFObj = new Invoice();
			elseif($bean->trans_type == 'CreditMemo')
				$PDFObj= new CreditMemo();

			//GET INVOICE PRODUCT ITEMS AND PRICE
			$items = array();
			for($k=1;$k<5;$k++)
			{
				$itemID = 'trann_items_id'.$k.'_c';
				$price = 'amount_'.$k;
				if($bean->$itemID != '' && $bean->$price != 0)
				{
					$itemObj = BeanFactory::getBean('TRANN_Items');
					$itemObj->retrieve($bean->$itemID);

					$taxObj = BeanFactory::getBean('TAXRA_Tax');
					$taxObj->retrieve($itemObj->taxra_tax_id_c);

					$items[$k]['name'] = $itemObj->display_name;
					$items[$k]['price'] = number_format($bean->$price,2);

					$amount_net = round((float) $bean->$price / (1 + (float) $taxObj->rate / 100), 2);
					$amount_tax = round((float) $bean->$price - (float) $amount_net, 2);

					$items[$k]['amount_net'] = number_format($amount_net,2);
					$items[$k]['amount_tax'] = number_format($amount_tax,2);

				}
			}

            $bean->load_relationship('trann_trans_trann_payments_1');
            $PayList = $bean->trann_trans_trann_payments_1->getBeans();
            $j=0;
            $payments = array();
            foreach($PayList as $pay)
            {
                $payMethod = BeanFactory::getBean('TRANN_Payment_Method',$pay->trann_payment_method_id_c);
                $payments[$j]['number'] = $pay->name;
                $payments[$j]['method'] = $payMethod->name;
                $payments[$j]['date'] = $pay->payment_date;
                if($pay->trann_trans_id1_c == $bean->id)
                    $payments[$j]['amount'] = number_format($pay->amount_applied_1,2);
                if($pay->trann_trans_id2_c == $bean->id)
                    $payments[$j]['amount'] = number_format($pay->amount_applied_2,2);
                if($pay->trann_trans_id3_c == $bean->id)
                    $payments[$j]['amount'] = number_format($pay->amount_applied_3,2);
                $j++;
            }


			$bean->amount_remaining = number_format($bean->amount_remaining,2);
			$bean->amount_total = number_format($bean->amount_total,2);

			//CREATE INVOICE
			$PDFObj->Generate_PDF($bean,$clientObj,$payments,$items);

		}
	}



	function Email_Invoice($bean, $event, $arguments)
	{

		if($bean->send_invoice_email == 1 && ($bean->trans_type == 'CreditMemo' || $bean->trans_type == 'Invoice'))
		{
			$clientId = $this->_get_related_id($bean,'contacts_trann_trans_1contacts_ida','contacts_trann_trans_1');
			$clientObj = BeanFactory::getBean('Contacts');
			$clientObj->retrieve($clientId);
            $sale_person = $this->_getSalesPerson($clientObj);

            $itemObj = BeanFactory::getBean('TRANN_Items',$bean->trann_items_id1_c);
            $PS ='';

			//GET EMAIL TEMPALTE FOR INVOICE
			$emailTemp = BeanFactory::getBean('EmailTemplates');
			if($bean->trans_type == 'Invoice')
            {
                if($bean->amount_paid == 0)
                    $emailTemp->retrieve($this->INVOICE_ZERO_TEMPLATE);
                else
                {
                    $emailTemp->retrieve($this->INVOICE_TEMPLATE);
                }
            }
			elseif($bean->trans_type == 'CreditMemo')
				$emailTemp->retrieve($this->CREDIT_MEMO_TEMPLATE);
			$emailObj = new Email();
			$defaults = $emailObj->getSystemDefaultEmail();

			//SET TO ADDRESSES
			$toAddresses = array();
			$toAddresses[$clientObj->first_name.' '.$clientObj->last_name] = $clientObj->email1;
			if($bean->email_address != '')
			{
				$extraEmails = explode(',',$bean->email_address);

				foreach($extraEmails as $email){
					$toAddresses[$email] = $email;

				}
			}

			//REPLACE VALUES FOR KEYS IN EMAIL TEMPLATE
			$data = array($clientObj->first_name.' '.$clientObj->last_name,$clientObj->first_name,$bean->amount_total,$itemObj->display_name, $sale_person['sales_full_name'],$sale_person['sales_first_name']);
			$keys = array('$contact_name','$contact_first_name','$amount','$product','$sales_full_name','$sales_first_name');

			$body = str_replace($keys,$data,$emailTemp->body_html);

            $subject = str_replace($keys,$data,$emailTemp->subject);


			//ATTACH INVOICE PDF
			$attachedFiles =array(array('file_location'=> "cache/invoices/",'filename'=>$bean->name.".pdf"));

			//SEND EMAIL
			$this->_Send_Email($this->BILLING_EMAIL,$defaults['name'],$toAddresses,$subject.' - '.$bean->name,'Contacts',$clientObj->id,$body,$attachedFiles,true);

		}
	}

  }
 
  
?>
