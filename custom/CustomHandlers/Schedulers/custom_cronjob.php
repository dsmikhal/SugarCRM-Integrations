<?php
	
	include_once("custom/CustomHandlers/Global_Functions.php");

Function JobsFailure()
{
    global $sugar_config;
    $gb = new Global_Functions();

    $GLOBALS['log']->error("JOBS: JobsFailure: Started.");
    $bean = BeanFactory::getBean('E_Enrolments');

    $j=0;
    $rstSQL = $bean->db->query("SELECT name, (execute_time + interval 11 hour) as exec_time, `status`, resolution, message FROM job_queue
                                where resolution = 'failure' and execute_time > (now() - interval 12 hour) order by execute_time DESC;");

    $jobs = '<table border = 1><tbody><tr><td><b>Job Name</b></td><td><b>Exec Date</b></td><td><b>Status</b></td><td><b>Result</b></td><td><b>Message</b></td></tr>';
    // Failure JOBS
    while($row = $bean->db->fetchByAssoc($rstSQL)){
        if($j==0) $j=1;
        $jobs.= "<tr><td>".$row['name']."</td><td>".$row['exec_time']."</td><td>".$row['status']."</td><td>".$row['resolution']."</td><td>".$row['message']."</td></tr>";
    }

    //Hangs Up Jobs
    $rstSQL = $bean->db->query("SELECT name, (execute_time + interval 11 hour) as exec_time, `status`, resolution, message FROM job_queue
                                where status = 'running' and (execute_time + interval 11 hour) < (now() - interval 1 hour) order by execute_time DESC");

    while($row = $bean->db->fetchByAssoc($rstSQL)){
        if($j==0) $j=1;
        $jobs.= "<tr><td>".$row['name']."</td><td>".$row['exec_time']."</td><td>".$row['status']."</td><td>".$row['resolution']."</td><td>".$row['message']."</td></tr>";
    }

    $jobs.="</tbody></table>";

    if($j==1)
    {
        $strEmailBody = "<html><body><strong>Alarm notification about failure Jobs in CRM</strong><br>".$jobs."<br><br> Site: <a href='http://pumpkin.crm/'>Pumpkin CRM</a></body></html>";

        $emailObj = new Email();
        $defaults = $emailObj->getSystemDefaultEmail();

        $toAddresses = array();
        $toAddresses['Techsupport'] = $sugar_config['alarm_it_address'];

        //SEND EMAIL
        if($gb->_Send_Email($defaults['email'],$defaults['name'],$toAddresses,'CRM: Failure or Hangs up JOBS Alarm.','','',html_entity_decode($strEmailBody),array(),false)){
            $GLOBALS['log']->error("JobsFailure: List of failure Jobs has been sent to IT.");
            return true;
        }
        else{
            $GLOBALS['log']->fatal("JobsFailure: Error send Email.");
            return false;
        }

    }
    $GLOBALS['log']->error("JOBS: JobsFailure: Finished.");

    return true;
}
