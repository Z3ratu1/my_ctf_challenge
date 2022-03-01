<?php
require_once 'config.php';
if (!isset($_SESSION['admin'])) {
	header('location:index.php');
}
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<title>fxxkcors</title>

		<link rel="stylesheet" href="http://libs.baidu.com/bootstrap/3.0.3/css/bootstrap.min.css" />
	</head>
	<body>
		<nav class="navbar navbar-inverse navbar-fixed-top">
			<div class="container">
		    	<div class="navbar-header">
		    		<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
		            	<span class="sr-only">Toggle navigation</span>
		            	<span class="icon-bar"></span>
		            	<span class="icon-bar"></span>
		            	<span class="icon-bar"></span>
		          	</button>
		          	<a class="navbar-brand" href="#">SUSCTF</a>
		        </div>
		        <div id="navbar" class="collapse navbar-collapse">
		          	<ul class="nav navbar-nav">
		          		<li class="active"><a href="/home.php">main</a></li>
		            	<li class="active"><a href="/change.php">change permission</a></li>
		            	<!--<li class="active"><a href="/require.php">权限申请</a></li>-->
                        <li class="active"><a href="/logout.php">logout</a></li>
		          	</ul>
		        </div>
		    </div>
		</nav>
		<div class="container" style="margin-top: 50px">
            <h1>hello,<?php echo htmlspecialchars($_SESSION['username']); ?></h1>
<?php
$sql = new User();
if ($sql->find_super(addslashes($_SESSION['username']))) {
	$_SESSION['admin'] = 'normal';
}
if ($_SESSION['admin'] == 'nop') {
	echo '<p>normal admin can see the flag</p>';
} else if ($_SESSION['admin'] == 'normal') {
	echo '<p>flag is ：' . file_get_contents('/flag') . '</p>';
} else if ($_SESSION['admin'] == 'super') {
	echo '<p>wwwww</p>';
}
?>
		</div>


