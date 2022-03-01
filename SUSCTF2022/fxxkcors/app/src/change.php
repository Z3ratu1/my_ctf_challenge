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
		          		<li class="active"><a href="./home.php">main</a></li>
		            	<li class="active"><a href="./change.php">change permission</a></li>
		            	<!--<li class="active"><a href="./require.php">权限申请</a></li>-->
                        <li class="active"><a href="./logout.php">logout</a></li>
		          	</ul>
		        </div>
		    </div>
		</nav>
		<div class="container" style="margin-top: 50px">
			<p>submit the person u want to be an normal admin</p>

		<form method="post" action="#" >
            <label>
                <input type="text" name="username" id = "uuu"/>
                <input type="button" value="submit" onClick="submitRequest(document.getElementById('uuu').value)">
            </label>
        </form>
         <script style="text/javascript">
      function submitRequest(username)
      {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "changeapi.php", true);
        xhr.setRequestHeader("Accept", "application/json, text/plain, */*");
        xhr.setRequestHeader("Accept-Language", "zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3");
        xhr.setRequestHeader("Content-Type", "application/json; charset=utf-8");
        xhr.withCredentials = true;
        xhr.send(JSON.stringify({'username':username}));
        xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
        	if(xhr.responseText === 'nop'){
        		alert('no permission or something wrong');
        	}
        	else{
        		alert('success');
        	}

        }
      }
    }

   </script>
		</div>


