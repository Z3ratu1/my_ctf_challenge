<?php
require_once 'config.php';

if (isset($_POST['username']) && isset($_POST['password'])) {
    if($_POST['username'] === 'admin'){
        if($_POST['password'] === ($_ENV['admin_pass'] ?? "admin114514aAtq1_lMytQl1sTqqqqql^hsdooiha")) {
            $_SESSION['username'] = $_POST['username'];
            $_SESSION['admin'] = 'super';
        }
        else{
            die("user exist!");
        }
    }else {
        $sql = new User();
        $res = $sql->find_normal($_POST['username']);
        if (!$res) {
            $sql->insert_normal($_POST['username'], $_POST['password']);
            $_SESSION['username'] = $_POST['username'];
            $_SESSION['admin'] = 'nop';
        } else {
            if($res['password'] === $_POST['password']){
                $_SESSION['username'] = $_POST['username'];
                $_SESSION['admin'] = 'nop';
            }
            else {
                die("user exist!");
            }
        }
    }
	header('Location:home.php');
} else {
	die('no use');
}
?>