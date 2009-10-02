<?php
function create_mysql_connection($db_user, $db_pass, $db_name, $db_host){
	$db_resource = mysql_connect($db_host, $db_user, $db_pass) or die("Не могу создать соединение"); 
	mysql_select_db($db_name);
	
	return $db_resource;
}
?>