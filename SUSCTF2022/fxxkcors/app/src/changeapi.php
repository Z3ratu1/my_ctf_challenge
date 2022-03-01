<?php
require_once 'config.php';
if (!isset($_SESSION['admin'])) {
	header('location:index.php');
}
$json = file_get_contents('php://input');

if ($json) {
	$json = json_decode($json);
	if ($_SESSION['admin'] == 'super' && $json->username) {
		$sql = new User();
		$sql->insert_super(addslashes($json->username));
	} else {
		echo 'nop';
	}
}

?>