<?php
require_once('custom/CustomHandlers/Global_Functions.php');
require_once("custom/CustomHandlers/fpdf.php");

class E_EventsHandler extends Global_Functions
{
    Function PrintTickets($bean,$event,$arguments)
    {
        if($bean->print_tickets_c == 1)
        {
            $sql = $GLOBALS['db']->query("SELECT c.first_name,c.last_name, cc.account_code_c,c.primary_address_state, e.name,e.ticket_type
                  FROM e_enrolments e, e_events_e_enrolments_1_c ee,
                       contacts_e_enrolments_1_c ce, contacts c, contacts_cstm cc
                 WHERE (e.status = 'confirmed' OR e.status='PartPaid') AND e.id=ee.e_events_e_enrolments_1e_enrolments_idb
                   AND ee.e_events_e_enrolments_1e_events_ida='{$bean->id}'
                   and e.deleted=0 AND ce.deleted=0 AND c.deleted = 0
                   AND ce.contacts_e_enrolments_1contacts_ida=c.id AND ce.contacts_e_enrolments_1e_enrolments_idb=e.id
                   AND c.id=cc.id_c
                   order by e.ticket_type");

            $pdf = new FPDF();

            while($row = $GLOBALS['db']->fetchByAssoc($sql))
            {
                $gt='';
                if($row['ticket_type']=='Resit') $gt = 'Graduate';
                elseif ($row['ticket_type']=='Elite') $gt = 'Elite';

                $pdf->AddPage();
                $pdf->SetAutoPageBreak(false);
                $pdf->SetFont('Arial','',16);
                $pdf->Code39(110, 5, $row['account_code_c']);
                $pdf->ln(15);
                $pdf->Cell(0,0,$bean->name, 0, 1, 'R');

                $pdf->SetFont('Arial','B',8);
                $pdf->ln(10);
                $pdf->Cell(0,0,'Participant Agreement and Release Form - '.$row['first_name'].' '.$row['last_name'], 0, 1, 'L');
                $pdf->ln(4);
                $pdf->SetFont('Arial','',8);
                $pdf->MultiCell(0,4,'Read the terms and conditions outlined below carefully. You may only proceed to enter the room where the training seminar is being held if you fully accept and agree to these terms and conditions in addition to the original terms and conditions which you signed upon purchasing the product.', 0, 'J');
                $pdf->ln(3);
                $pdf->MultiCell(0,4,'By signing the below I accept and agree to the following terms and conditions', 0, 'J');
                $pdf->ln(3);
                $pdf->Cell(5);
                $pdf->Cell(5,4,'1.', 0, 0, 'L');
                $pdf->MultiCell(0,4,'You warrant that you have purchased this product for the sole purpose of self-education. You acknowledge that the nominated Training Package and (if applicable) other additional products are based on example or demonstration trades only. You warrant and represent that you will not engage in live trading based on any information or examples provided and that if you do so you indemnify Pumpkin Pty Ltd and its associated companies in relation to all claims and losses suffered by you or anyone else in relation to that trade.', 0, 'J');
                $pdf->ln(3);
                $pdf->Cell(20);
                $pdf->Cell(20,8,'Signature', 0, 0, 'R');
                $pdf->Cell(50,8,'', 1, 0, 'L');
                $pdf->Cell(30,8,'Date', 0, 0, 'R');
                $pdf->Cell(30,8,'', 1, 0, 'L');
                $pdf->ln(20);
                $pdf->SetFont('Arial','',30);
                $pdf->Cell(105,0,$row['first_name'], 0, 0, 'L');
                $pdf->Cell(0,0,$row['first_name'], 0, 0, 'L');
                $pdf->ln(15);
                $pdf->Cell(105,0,$row['last_name'], 0, 0, 'L');
                $pdf->Cell(0,0,$row['last_name'], 0, 0, 'L');
                $pdf->ln(10);
                $pdf->Cell(60);
                $pdf->SetFont('Arial','',16);
                $pdf->Cell(105,0,$row['primary_address_state'], 0, 0, 'L');
                $pdf->Cell(0,0,$row['primary_address_state'], 0, 0, 'L');
                $pdf->Code39(5, 265, $row['account_code_c']);
                $pdf->Code39(110, 265, $row['account_code_c']);
                $pdf->ln(25);
                $pdf->Cell(60);
                $pdf->SetFont('Arial','',14);
                $pdf->Cell(105,0,$gt, 0, 0, 'L');
                $pdf->Cell(0,0,$gt, 0, 0, 'L');

            }

            $pdf->Output('cache/EventRef'.$bean->event_ref.'Tickets.pdf','F');

            $bean->print_tickets_c = 0;

            if(!empty($bean->note_id_c))
            {
                $pdf = file_get_contents('cache/EventRef'.$bean->event_ref.'Tickets.pdf');
                file_put_contents('upload/'.$bean->note_id_c, $pdf);
            }

            else
            {
                $Notes = BeanFactory::getBean('Notes');
                $Notes->name = 'EventRef'.$bean->event_ref.'Tickets.pdf';
                $Notes->file_mime_type = 'pdf';
                $Notes->filename = 'EventRef'.$bean->event_ref.'Tickets.pdf';
                $Notes->parent_type = 'E_Events';
                $Notes->parent_id = $bean->id;
                $Notes->save();
                $pdf = file_get_contents('cache/EventRef'.$bean->event_ref.'Tickets.pdf');
                file_put_contents('upload/'.$Notes->id, $pdf);
            }
            $bean->note_id_c = $Notes->id;
        }
    }
}