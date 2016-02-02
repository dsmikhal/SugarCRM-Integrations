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
	


  }
 
  
?>
