<?php

class db {
	
	function db(){
		return true;
	}
	
	function connect($host,$user,$pass,$base){
		$db = mysql_connect($host,$user,$pass);
		if(!$db){
			die("Ошибка подключения к БД: ".mysql_error());
		}
		
		if(!mysql_select_db($base,$db)){
			die("Ошибка выбора БД: ".mysql_error());
		}
		mysql_set_charset('utf8');
		mysql_query("SET NAMES 'utf8'");
		return $db;
	}
	
	function free_query($res){
		return @mysql_free_result($res);
	}

	function query($query){
		$responce = mysql_query($query);
		if(!$responce){
			die("<font style=\"color:red;font-weight:bold;\">Плохой запрос: {$query}\r\n".mysql_error()."</font>\r\n");
		} else {
			return $responce;
		}
	}
	
	function return_row($query){
		return mysql_fetch_assoc($query);
	}
	
	function query_row($query){
		if($query!=''){
			$q = $this->query($query);
			return $this->return_row($q);
		} else {
			return false;
		}
	}
	
	function affected_rows(){
		return mysql_affected_rows();
	}
	
	function affected_rows_sel(){
		return mysql_num_rows();
	}
	
	function close($res) {
		mysql_close($res);
	}
	
}

?>