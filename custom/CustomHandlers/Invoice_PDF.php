<?php


class Invoice
{
	
	function Generate_PDF($invoiceObj,$clientObj,$payments = array(),$items){
		
		global $sugar_config;
		require_once('custom/include/dompdf/dompdf_config.inc.php');
		spl_autoload_register('DOMPDF_autoload'); 
		
		$img_path =realpath(dirname(__FILE__));
		
		
		if($invoiceObj->company_name == '')
			$company = $clientObj->company_name_c;
		else
			$company = $invoiceObj->company_name;
	
		// Build HTML
		$html = <<<EOD
		<style>
		td{
			background-color:white;
			vertical-align:top;
		}
		</style>
		<table style="font-family: helvetica; font-size: 12px; width: 100%;" border="0" cellspacing="10" cellpadding="0">
		<tbody>
		<tr>
		<td colspan=2 align="center"><img src="{$img_path}/TP_TAI_Trans.gif"></td>
		</tr>
		<tr>
		<td width="80%"><h3>TO </h3> {$clientObj->first_name} {$clientObj->last_name} <br /> {$company} <br /> {$clientObj->primary_address_street}<br /> {$clientObj->primary_address_city}<br /> {$clientObj->primary_address_state}  {$clientObj->primary_address_country}  {$clientObj->primary_address_postalcode}</td>
		<td><h3>TAX INVOICE </h3> Invoice  : {$invoiceObj->name} <br /> Date : {$invoiceObj->document_date}</td>
		</tr>
		</tbody>
		</table>
		<table style="font-family: helvetica; font-size: 10px; width: 100%;background-color:black;" border="0" cellspacing="2" cellpadding="5">
		<tbody>
		<tr>
		<td><strong>Quantity</strong></td>
		<td><strong>Description</strong></td>
		<td><strong>Item Price <br> (Inc. GST)</strong></td>
		<td><strong>Total Amount <br>(Ex. GST)</strong></td>
		<td><strong>Total GST</strong></td>
		<td><strong>Total Price</strong></td>
		</tr>
EOD;

		foreach($items as $item)
		{
		$html .= "
		<tr>
		<td>1</td>
		<td>{$item['name']}</td>
		<td>{$sugar_config['default_currency_symbol']}{$item['price']}</td>
		<td>{$sugar_config['default_currency_symbol']}{$item['amount_net']}</td>
		<td>{$sugar_config['default_currency_symbol']}{$item['amount_tax']}</td>
		<td>{$sugar_config['default_currency_symbol']}{$item['price']}</td>
		</tr>";
		}
	
		$html .= "<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td><b>TOTAL</b></td>
		<td>{$sugar_config['default_currency_symbol']}{$invoiceObj->amount_total}</td>
		</tr>";	
	

        if (count($payments) > 0)
        {
            foreach($payments as $payment)
            {
                $paymentdate = date('d-m-Y',strtotime($payment['date']));
                $html .= "<tr>
                <td>&nbsp;</td>
                <td colspan='4'>Payment REF {$payment['number']} : Payment Received ON {$paymentdate} By {$payment['method']}</td>
                <td>{$sugar_config['default_currency_symbol']}{$payment['amount']}</td>
                </tr>";
            }
        }
        else
        {
            $html .= "<tr>
			<td>&nbsp;</td>
			<td colspan='4'>Amount paid</td>
			<td>{$sugar_config['default_currency_symbol']}0.00</td>
			</tr>";
        }

	
		$html .= "<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td colspan='3'>All Prices in AUD unless specified otherwise</td>
		</tr>
		<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td colspan='2'>
		<b>BALANCE OUTSTANDING</b>
		</td>
		<td>{$sugar_config['default_currency_symbol']}{$invoiceObj->amount_remaining}</td>
		</tr>
		</tbody>
		</table>
		<br><br>
		<div style='font-family: helvetica; font-size: 10px; width: 100%;'><center>{$sugar_config['company_address']}</center></div>
		";

		$dompdf = new DOMPDF();
		$dompdf->load_html($html);
		
		$dompdf->render();
		//$dompdf->stream("asdasdas.pdf");
	
		$pdf = $dompdf->output();
		if(!is_file('cache/invoices'))
			mkdir('cache/invoices',0775);
		
		file_put_contents("cache/invoices/".$invoiceObj->name.".pdf", $pdf);
	}

}
?>