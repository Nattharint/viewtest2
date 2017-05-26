<?php
/**
* @project ApPHP Shopping Cart
* @copyright (c) 2012 ApPHP
* @author ApPHP <info@apphp.com>
* @license http://www.gnu.org/licenses/
*/

// prepare reading of SQL dump file and executing SQL statements
function apphp_db_install($sql_dump_file) {
	global $error_mg;
	global $username;
	global $password;
	global $database_prefix;
	global $password_encryption;
	
	$sql_array = array();
	$query = '';
	
	// get  sql dump content
	$sql_dump = file($sql_dump_file);		
	
	// replace database prefix if exists
	$sql_dump = str_ireplace('<DB_PREFIX>', $database_prefix, $sql_dump);
								
    // disabling magic quotes at runtime
    if(get_magic_quotes_runtime()){
        function stripslashes_runtime(&$value){
            $value = stripslashes($value);	
        }
        array_walk_recursive($sql_dump, 'stripslashes_runtime');
    }

	// add ";" at the end of file
	if(substr($sql_dump[count($sql_dump)-1], -1) != ';') $sql_dump[count($sql_dump)-1] .= ';';

	// replace username and password if exists
	if(EI_USE_USERNAME_AND_PASWORD){
		$sql_dump = str_ireplace('<USER_NAME>', $username, $sql_dump);
		if(EI_USE_PASSWORD_ENCRYPTION){
			if($password_encryption == 'AES'){
				$sql_dump = str_ireplace('<PASSWORD>', 'AES_ENCRYPT(\''.$password.'\', \''.EI_PASSWORD_ENCRYPTION_KEY.'\')', $sql_dump);
			}else if($password_encryption == 'MD5'){
				$sql_dump = str_ireplace('<PASSWORD>', 'MD5(\''.$password.'\')', $sql_dump);
			}else{
				$sql_dump = str_ireplace('<PASSWORD>', 'AES_ENCRYPT(\''.$password.'\', \''.EI_PASSWORD_ENCRYPTION_KEY.'\')', $sql_dump);				
			}
		}else{
			$sql_dump = str_ireplace('<PASSWORD>', '\''.$password.'\'', $sql_dump);
		}
	}		

	// encode connection, server, client etc.
	if(EI_USE_ENCODING){
		$sql_variables = array(
			'character_set_client'  =>EI_DUMP_FILE_ENCODING,
			'character_set_server'  =>EI_DUMP_FILE_ENCODING,
			'character_set_results' =>EI_DUMP_FILE_ENCODING,
			'character_set_database'=>EI_DUMP_FILE_ENCODING,
			'character_set_connection'=>EI_DUMP_FILE_ENCODING,
			'collation_server'      =>EI_DUMP_FILE_COLLATION,
			'collation_database'    =>EI_DUMP_FILE_COLLATION,
			'collation_connection'  =>EI_DUMP_FILE_COLLATION
		);
		foreach($sql_variables as $var => $value){
			$sql = 'SET '.$var.'='.$value.';';
			@mysql_query($sql);
		}			
	}		
	
	foreach($sql_dump as $sql_line){ 
		$tsl = trim(utf8_decode($sql_line));
		if(($sql_line != '') && (substr($tsl, 0, 2) != '--') && (substr($tsl, 0, 1) != '?') && (substr($tsl, 0, 1) != '#')) {
			$query .= $sql_line;
			if(preg_match('/;\s*$/', $sql_line)){
				if((strlen(trim($query)) > 5) && !@mysql_query($query)){	
					$error_mg[] = mysql_error();
					return false;
				}
				$query = '';
			}
		}
	}
	return true;
}

function apphp_db_select_db($database) {
	return mysql_select_db($database);
}

function apphp_db_query($query) {
	global $link;
	$res=mysql_query($query, $link);
	return $res;
}

/**
 * 	Creates a random string with characters 1-10 and a-z
 * 		@param length - the length of the random string 	
 **/
function random_string($length = 10)
{
	$rand_string = '';
	for ($i = 0; $i < $length; $i++) {
		$x = mt_rand(0, 35);
		if ($x > 9) $rand_string .= chr($x + 87);
		else $rand_string .= $x;
	}
	return $rand_string;
}

/**
 *	Remove bad chars from input
 *	  	@param $str_words - input
 **/
function prepare_input($str_words, $escape = false, $level = 'high')
{
    $found = false;
    $str_words = htmlentities(strip_tags($str_words));
    if($level == 'low'){
        $bad_string = array('drop', '--', 'insert', 'xp_', '%20union%20', '/*', '*/union/*', '+union+', 'load_file', 'outfile', 'document.cookie', 'onmouse', '<script', '<iframe', '<applet', '<meta', '<style', '<form', '<body', '<link', '_GLOBALS', '_REQUEST', '_GET', '_POST', 'include_path', 'prefix', 'ftp://', 'smb://', 'onmouseover=', 'onmouseout=');
    }else if($level == 'medium'){
        $bad_string = array('select', 'drop', '--', 'insert', 'xp_', '%20union%20', '/*', '*/union/*', '+union+', 'load_file', 'outfile', 'document.cookie', 'onmouse', '<script', '<iframe', '<applet', '<meta', '<style', '<form', '<body', '<link', '_GLOBALS', '_REQUEST', '_GET', '_POST', 'include_path', 'prefix', 'ftp://', 'smb://', 'onmouseover=', 'onmouseout=');		
    }else{
        $bad_string = array('select', 'drop', '--', 'insert', 'xp_', '%20union%20', '/*', '*/union/*', '+union+', 'load_file', 'outfile', 'document.cookie', 'onmouse', '<script', '<iframe', '<applet', '<meta', '<style', '<form', '<img', '<body', '<link', '_GLOBALS', '_REQUEST', '_GET', '_POST', 'include_path', 'prefix', 'http://', 'https://', 'ftp://', 'smb://', 'onmouseover=', 'onmouseout=');
    }
    for($i = 0; $i < count($bad_string); $i++){
        $str_words = str_replace($bad_string[$i], '', $str_words);
    }
    
    if($escape){
        $str_words = mysql_real_escape_string($str_words); 
    }
    
    return $str_words;
}

?>