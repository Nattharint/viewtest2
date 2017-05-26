<?php
/**
* @project ApPHP Shopping Cart
* @copyright (c) 2012 ApPHP
* @author ApPHP <info@apphp.com>
* @license http://www.gnu.org/licenses/
*/

//--------------------------------------------------------------------------
// *** remote file inclusion, check for strange characters in $_GET keys
// *** all keys with "/", "\", ":" or "%-0-0" are blocked, so it becomes virtually impossible
// *** to inject other pages or websites
foreach($_GET as $get_key => $get_value){
    if(is_string($get_value) && (preg_match("/\//", $get_value) || preg_match("/\[\\\]/", $get_value) || preg_match("/:/", $get_value) || preg_match("/%00/", $get_value))){
        if(isset($_GET[$get_key])) unset($_GET[$get_key]);
        die("A hacking attempt has been detected. For security reasons, we're blocking any code execution.");
    }
}

require_once('include/base.inc.php');
require_once('include/connection.php');

$download_code = isset($_GET['dc']) ? prepare_input($_GET['dc']) : '';

$query_string = unserialize(base64_decode($download_code));
$customer = isset($query_string['customer']) ? prepare_input($query_string['customer']) : '';
$order    = isset($query_string['order']) ? prepare_input($query_string['order']) : '';
$product  = isset($query_string['product']) ? prepare_input($query_string['product']) : '';

if($objLogin->IsLoggedInAsCustomer() && ($customer == $objLogin->GetLoggedID())){

	$file_path = 'downloads/';
	$sql = 'SELECT
			p.product_file
		FROM '.TABLE_ORDERS.' o
			INNER JOIN '.TABLE_ORDERS_DESCRIPTION.' od ON o.order_number = od.order_number
			INNER JOIN '.TABLE_PRODUCTS.' p ON od.product_id = p.id
		WHERE
			DATE_SUB(\''.date('Y-m-d H:i:s').'\',INTERVAL '.DIGITAL_PRODUCT_DOWNLOAD_EXPIRE.' DAY) <= o.payment_date AND
			p.product_type = 1 AND
			p.id = '.(int)$product.' AND
           (o.status = 2 OR o.status = 5) AND 
			o.order_number = \''.$order.'\' AND 
			o.customer_id = '.(int)$customer;

	$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
	if($result[1] > 0){
		if($result[0]['product_file'] != ''){
			download_file($file_path.$result[0]['product_file'], $result[0]['product_file']);
			exit(0);
		}else{
			header('location: index.php?customer=downloads');
			exit(0);		
		}
	}else{
		header('location: index.php?customer=downloads');
		exit(0);		
	}
}else{
	echo _NOT_AUTHORIZED;	
}


function download_file($file_path, $file){
	
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: no-cache'); // HTTP/1.0

	header('Content-type: application/force-download'); 
	header('Content-Disposition: inline; filename="'.$file.'"'); 
	header('Content-Transfer-Encoding: Binary'); 
	header('Content-length: '.filesize($file_path)); 
	header('Content-Type: application/octet-stream'); 
	header('Content-Disposition: attachment; filename="'.$file.'"'); 
	readfile($file_path);
	
}

?>