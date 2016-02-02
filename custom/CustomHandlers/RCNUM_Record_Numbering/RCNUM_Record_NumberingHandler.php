<?php
/**
* ITonTap Record Numbering hook. This hook is implemented for Record Numbering custom module.
*/
define('ITTAP_RCNUM_MODULE','RCNUM_Record_Numbering');

class RecordNumbering {

	public static function RCNUM_Record_Numbering($focus, $event) {
		if(empty($focus->fetched_row)){ // || empty($focus->account_code_c)){  
			self::ittap_rcnum($focus->module_name,$focus);
		}
	}
	
    /**
    * Check if the record numbering exist.
    * @return bool
    */
    public static function isRecordNumberingExist() {
        $bean_name = ITTAP_RCNUM_MODULE;
        if (!(class_exists($bean_name))) {
            if (isset($GLOBALS['beanList']) && isset($GLOBALS['beanFiles'])) global $beanFiles;
            else require_once('include/modules.php');
            if (isset($beanFiles[$bean_name])) {
                $bean_file=$beanFiles[$bean_name];
                include_once($bean_file);
                return true;
            } else return false;
        }
        return true;
    }

    /**
    * Generate record numbering.
    *
    * @param string $module
    * @param DBHelper $db
    * @return array List of field with generated record numbering.
    */
    public static function ittap_rcnum($module,&$bean) {
        //if (! self::isRecordNumberingExist()) return;
        $db = $bean->db;
        $class = ITTAP_RCNUM_MODULE;
        $obj = new $class();
        $table = $obj->getTableName();
        $result = $db->query("SELECT field_name,id from $table WHERE module_id_name = '$module' and deleted = 0 and active = 1");
        $return = array();
        while ($row = $db->fetchByAssoc($result)) {
			$field = $row['field_name'];
            $bean->$field = self::ittap_issue_rcnum($row['id'],$db);
        }
    }

    /**
    * This is the workhorse that generate the record numbering.
    *
    * @param string $id GUID of the record.
    * @param DBHelper $db
    * @return string
    */
    private static function ittap_issue_rcnum($id,$db) {
        $table = strtolower(ITTAP_RCNUM_MODULE);
        $res = $db->query("lock tables $table write");
        $sql = "SELECT * FROM $table WHERE id = '$id'";
        $result = $db->query($sql);
        $row = $db->fetchByAssoc($result);
        if ($row['random'] == 1) {
            $db->query('unlock tables');
            return self::ittap_issue_token($row,$db);
        }
        $counter = $row['current_value'];
        $prefix = $row['prefix'];
        $suffix = $row['suffix'];
        # enhanced for more complex numbers
        $inc = isset($row['increment']) ? $row['increment'] : 1;
        $counter = $counter + $inc;
        $base = isset($row['value_base']) ? $row['value_base'] : 10;
        if ($base < 10) $base = 10;
        if ($base > 10) $code = self::ittap_next_rcnum($counter,$inc,$base);
        else $code = $counter;
        $sql = "UPDATE $table SET current_value = $counter where id = '$id'";
        $db->query($sql);
        $db->query('unlock tables');
        # zero pad if less than min length
        if (strlen($code) < $row['value_min_length']) $code = str_pad($code,$row['value_min_length'],'0',STR_PAD_LEFT);
        return $prefix.$code.$suffix;
    }

    /**
    * Special function if there are letters involved.
    * We want to skip codes which potentially contain 4-letter words...
    *
    * counter is already incremented once before calling this
    * it updates the counter via ref if necessary
    * and returns the code
    *
    * @param string $counter
    * @param int $inc
    * @param int $base
    * @return string
    */
    private static function ittap_next_rcnum(&$counter,$inc,$base) {
        $code = strtoupper(base_convert($counter,10,$base));
        if (preg_match('/[AEIOU]/',$code)) {
            # we have vowels
            $parts = preg_split('/\d+/',$code);
            $word_max_length = 3;
            foreach ($parts as $word) {
                if ((strlen($word) > $word_max_length) and preg_match('/[AEIOU]/',$word)) {
                    $power = strlen($code) - (strpos($code,$word) + ($word_max_length + 1));
                    $place = $base - intval(substr($word,$word_max_length,1),$base);
                    $spinc = $place*pow($base,$power);
                    $counter=$counter+$spinc;
                    # force check sum - divisible by increment
                    while ($counter % $inc != 0) $counter++;
                        $code = strtoupper(base_convert($counter,10,36));
                }
            }
        }
        return $code;
    }


    /**
    * can also issue a unique randomly generated token/code instead of a sequential code...
    *
    * @param array $rcnum Databases Row
    * @param DBHelper $db
    */
    private static function ittap_issue_token($rcnum,$db) {
        if ($rcnum['check_unique'] == 0) return self::ittap_make_token($rcnum['value_min_length']);
        $code_unique = false;
        while (!$code_unique) {
            $token = self::ittap_make_token($rcnum['value_min_length']);
            $sql = "SELECT `{$rcnum['field_name']}` FROM `{$rcnum['module_id_name']}` WHERE `{$rcnum['field_name']}` = '$token';";
            $result = $db->query($sql);
            $row = $db->fetchByAssoc($result);
            if (empty($row[$rcnum['field_name']])) $code_unique = true;
        }
        return $token;
    }

 /**
    * Function to generate the token string.
    *
    * @param int $length Length of the token.
    * @return string token string.
    */
    private static function ittap_make_token($length) {
        $salt = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $len = strlen($salt);
        $token = '';
        mt_srand(10000000 * ( double ) microtime());

        # token has to include upper, lower and numeric
        $salt1 = "abcdefghijklmnopqrstuvwxyz";
        $len1 = strlen($salt1);
        $salt2 = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $len2 = strlen($salt2);
        $salt3 = "0123456789";
        $len3 = strlen($salt3);
        $token .= $salt2[mt_rand(0, $len2 -1)];
        $token .= $salt3[mt_rand(0, $len3 -1)];
        $token .= $salt1[mt_rand(0, $len1 -1)];

        for ($i = 0; $i < $length -3; $i++)
            $token .= $salt[mt_rand(0, $len -1)];

        return $token;
    }
}

?>
