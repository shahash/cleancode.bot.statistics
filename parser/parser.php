<?php
require_once('options.php');

$optionsParams = array(
        'verbose.level' => array(
                'required' => false,
                'argument' => 'required',
                'short_option' => 'v',
                'type' => 'enum',
                'possible_enum_values' => array(
                        'none',
                        'progress',
                        'fulllines'
                )
        ),
        'verbose.skiplines' => array(
                'required' => false,
                'argument' => 'required',
                'type' => 'int'
        ),
        'input.file' => array(
                'required' => true,
                'argument' => 'required',
                'short_option' => 'i'
        ),
        'buffering' => array(
        	'required' => false,
        	'short_option' => 'b',
        	'argument' => 'optional',
    		'type' => 'int',
        	'default' => 5
        )
);

if(!$options = parse_options($optionsParams))
{
        die("Usage: php parser.php [--verbose.level=<level> [--verbose.skiplines=<linescount>]] --input.file=<filename>\n");
}

$inputFile = $options['input.file'];
$verboseLevel = isset($options['verbose.level']) ? $options['verbose.level'] : 'none';
$verboseSkiplines = isset($options['verbose.skiplines']) ? $options['verbose.skiplines'] : 10;
$bufferSize = isset($options['buffering']) ? $options['buffering'] : 1;

//ICQ Number of Bot
define("ICQ_NUMBER", 573869459);
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

//Patterns
//[(day).(month).(year) (hour).(min).(sec)] - (command_type) - (data)
$patternGeneral='/\[(\d{2})\.(\d{2})\.(\d{4})\s(\d{2}):(\d{2}):(\d{2})\] - ([a-z]+) - (.+)/';
//(icq_number):"(message)"
$patternIncoming='/(^[0-9]{5,9}): "(.+)"$/';
//(command): (property) | (date) (day).(month).(year)
$patternData='/(^\w+): (\d{4}) \| (\w+): (\d{2}).(\d{2}).(\d{4})$/';
//(command) (properties)
$patternCmdcall='/(^\w+) (.+)$/';

require_once("db.php");
$db_res = create_mysql_connection();
mysql_query('SET NAMES UTF8');
$fh = fopen($inputFile,"r") or die("Не могу открыть файл\n");
echo "\n#Parse started.\n";
$tempTimeStampArray=mysql_fetch_array(mysql_query("SELECT `timestamp` FROM `log_entry` WHERE `id` = (SELECT MAX(`id`) FROM `log_entry`)"));
$maxTimeStamp=$tempTimeStampArray[0];
$countLines=0;

//Function
function pushData($tableName, $values, $countLines, $bufferSize){
	global $queryBodies;
	if(($countLines%$bufferSize)!=0){
		$queryBodies[$tableName].=',';
	} else {
		foreach($queryBodies as $currentTable => $currentQuery){
			if(!empty($currentQuery)) mysql_query($queryHeads[$currentTable].$currentQuery);
			$queryBodies[$currentTable]='';
			echo "Debug:$currentTable\n";
		}
	}
}

//Queries
$queryHeads = array(
	"log_entry" => "INSERT INTO `log_entry`(`timestamp`, `category_id`, `action_code`) VALUES ",
	"log_incoming" => "INSERT INTO `log_incoming`(`entry_id`, `uin`, `text`) VALUES ",
	"log_cmdcall" => "INSERT INTO `log_cmdcall`(`entry_id`, `cmd`, `params`) VALUES ",
	"log_cmdcall_schedule_group" => "INSERT INTO `log_cmdcall_schedule_group`(`entry_id`, `group`, `date`) VALUES "
	);

/*	
global $queryBodies=array(
			"log_entry" => "",
			"log_incoming" => "",
			"log_cmdcall" => "",
			"log_cmdcall_schedule_group" => ""
		);
*/

while (!feof($fh)){
	$countLines++;
	$line = trim(fgets($fh, 4096));
	if(preg_match($patternGeneral,$line,$result)){
		$timestamp = mktime($result[4],$result[5],$result[6],$result[2],$result[1],$result[3]); 
		if($timestamp<=$maxTimeStamp){continue;}
		switch($result[7]) {
			case 'general':
				$action_code=0;
			   //Change if's to preq_match
				if($result[8]=="libpurple initialized") $action_code = ACT_GENERAL_INIT;
				if($result[8]=="Account connected: ".ICQ_NUMBER." prpl-icq") $action_code = ACT_GENERAL_CONNECT;
				if($result[8]=="Account disconnected: ".ICQ_NUMBER." prpl-icq") $action_code = ACT_GENERAL_DISCONNECT;
				
				$queryBodies['log_entry'].="('{$timestamp}',".LOG_CATEGORY_GENERAL.",'{$action_code}')";
				pushData($queryEntryBegin, $queryEntry, $countLines, $bufferSize);
			break;
			
			case 'error':
				$action_code = 0;
				
				$queryBodies['log_entry'].="('{$timestamp}',".LOG_CATEGORY_ERROR.",'{$action_code}')";
				pushData($queryErrorBegin, $queryError, $countLines, $bufferSize);
			break;
			
			case 'incoming':
			
				$queryBodies['log_entry'].="('{$timestamp}',".LOG_CATEGORY_INCOMING.",".ACT_INCOMING_ALL.")";
				pushData($queryEntryBegin, $queryEntry, $countLines, $bufferSize);
				
				$entry_id = mysql_insert_id();
				
				if(preg_match($patternIncoming, $result[8], $incoming_data)){
					$incoming_data[2]=mysql_real_escape_string($incoming_data[2]);
					
					$queryBodies['log_incoming'].="('{$entry_id}','{$incoming_data[1]}','{$incoming_data[2]}')";
					pushData($queryIncomingBegin, $queryIncoming, $countLines, $bufferSize);
				}
			break;
			
			case 'cmdcall':
				$queryBodies['log_entry'].="('$timestamp',".LOG_CATEGORY_CMDCALL.",".ACT_CMDCALL_SCHEDULE_GROUP.")";
				pushData($queryEntryBegin, $queryEntry, $countLines, $bufferSize);
				
				$entry_id = mysql_insert_id();
	
				if(preg_match($patternCmdcall,$result[8],$cmd_call)){
				
					$queryBodies['log_cmdcall'].="('{$entry_id}',".ACT_CMDCALL_SCHEDULE_GROUP.",'{$cmd_call[2]}')";
					pushData($queryCmdcallBegin, $queryCmdcall, $countLines, $bufferSize);
				}
				if(preg_match($patternData,$cmd_call[2],$cmd_param)){
					$timestamp_schedule=mktime(0,0,0,$cmd_param[5],$cmd_param[4],$cmd_param[6]);
					
					$queryBodies['log_cmdcall_schedule_group'].="('{$entry_id}', '{$cmd_param[2]}', '{$timestamp_schedule}')";
					pushData($queryCmdcallScheduleBegin, $queryCmdcallSchedule, $countLines, $bufferSize);
				}
			break;
		}
		
	}else{ 
		if($verboseLevel == 'fulllines')
		{
			echo "~ $line : Wrong format.\n";
		}

	}
			
	if($verboseLevel == 'progress')
	{
		if($countLines % $verboseSkiplines == 0)
			echo "lines processed: $countLines\n";
	}
	else if($verboseLevel == 'fulllines')
		echo "~ $line : Done.\n";

} 
echo "#Parse completed.\n";	
fclose($fh);
mysql_close($db_res);	

?>