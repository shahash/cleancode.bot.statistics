<?php
function create_mysql_connection(){
	$db_user = 'root';
	$db_pass = 'root';
	$db_table = 'ifmobot';
	$db_resource = mysql_connect('localhost', $db_user, $db_pass) or die("�� ���� ������� ����������"); 
	mysql_select_db($db_table);
	
	return $db_resource;
}
?>