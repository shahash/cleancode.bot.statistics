<?php
if($argc !=2) die("Usage: php parser.php [filename]\n");

//Category's codes
define("LOG_CATEGORY_NONE", 0);
define("LOG_CATEGORY_GENERAL", 1);
define("LOG_CATEGORY_FUNC_CALL", 2);
define("LOG_CATEGORY_INCOMING", 4);
define("LOG_CATEGORY_CMDCALL", 8);
define("LOG_CATEGORY_ERROR", 15);
//Actions
//INCOMING
define("ACT_INCOMING_ALL", 1);
//GENERAL
define("ACT_GENERAL_INIT", 1);
define("ACT_GENERAL_CONNECT", 2);
define("ACT_GENERAL_DISCONNECT", 3);
//CMDCALL
define("ACT_CMDCALL_SCHEDULE_GROUP", 1);
//ERROR
define("ACT_ERROR_CURL_CANT_INIT",1);
define("ACT_ERROR_CURL_DATASOURCE_404",2);
define("ACT_ERROR_DATA_RECEIVE_FAIL",3);


require_once("db.php");
$db_res = create_mysql_connection();
mysql_query('SET NAMES UTF8');
$fh = fopen($argv[1],"r") or die("Не могу открыть файл\n");;
echo "\n#Parse started.\n\n";
while (!feof($fh)){
	$line = trim(fgets($fh, 4096));
	//[dayl.month.year hour.min.sec] - command_type - data
	$pattern="/\[(\d{2})\.(\d{2})\.(\d{4})\s(\d{2}):(\d{2}):(\d{2})\] - ([a-z]+) - (.+)/";
	if(preg_match($pattern,$line,$result)){
		$timestamp = mktime($result[4],$result[5],$result[6],$result[2],$result[1],$result[3]); 
		switch($result[7]) {
			case 'general':
				$action_code=0;
			   //Change if's to preq_match
				if($result[8]=="libpurple initialized") $action_code = ACT_GENERAL_INIT;
				if($result[8]=="Account connected: 573869459 prpl-icq") $action_code = ACT_GENERAL_CONNECT;
				if($result[8]=="Account disconnected: 573869459 prpl-icq") $action_code = ACT_GENERAL_DISCONNECT;
				
				mysql_query("INSERT INTO `log_entry`(`timestamp`, `category_id`, `action_code`) VALUES ('{$timestamp}',".LOG_CATEGORY_ERROR.",'{$action_code}')");
				
			break;
			
			case 'error':
				$action_code = 0;
				mysql_query("INSERT INTO `log_entry`(`timestamp`, `category_id`, `action_code`) VALUES ('{$timestamp}',".LOG_CATEGORY_ERROR.",'{$action_code}')");
			break;
			
			case 'incoming':
				mysql_query("INSERT INTO `log_entry`(`timestamp`, `category_id`, `action_code`) VALUES ('{$timestamp}',".LOG_CATEGORY_INCOMING.",".ACT_INCOMING_ALL.")");
				
				$entry_id = mysql_insert_id();
				
				if(preg_match('/(^[0-9]{5,9}): "(.+)"$/',$result[8],$incoming_data)){
					$incoming_data[2]=htmlspecialchars($incoming_data[2]);
					mysql_query("INSERT INTO `log_incoming`(`entry_id`, `uin`, `text`) VALUES ('{$entry_id}','{$incoming_data[1]}','{$incoming_data[2]}')");
				}
			break;
			
			case 'cmdcall':
				mysql_query("INSERT INTO log_entry(timestamp, category_id, action_code) VALUES ('$timestamp',".LOG_CATEGORY_CMDCALL.",".ACT_CMDCALL_SCHEDULE_GROUP.")");
				
				$entry_id = mysql_insert_id();
	
				if(preg_match('/(^\w+) (.+)$/',$result[8],$cmd_call)){
					mysql_query("INSERT INTO `log_cmdcall`(`entry_id`, `cmd`, `params`) VALUES ('$entry_id',".ACT_CMDCALL_SCHEDULE_GROUP.",'$cmd_call[2]')");
				}
				if(preg_match('/(^\w+): (\d{4}) \| (\w+): (\d{2}).(\d{2}).(\d{4})$/',$cmd_call[2],$cmd_param)){
					$timestamp_schedule=mktime(0,0,0,$cmd_param[5],$cmd_param[4],$cmd_param[6]);
					mysql_query("INSERT INTO `log_cmdcall_schedule_group`(`entry_id`, `group`, `date`) VALUES ('{$entry_id}', '{$cmd_param[2]}', '{$timestamp_schedule}')");
				}
			break;
		}
		
	}else{ 
		echo "Wrong format.";
	}
			
			echo "  $line : Done!\n";			
} 
echo "\n#Parse completed.\n";	
fclose($fh);
mysql_close($db_res);	

?>