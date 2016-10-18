<?php

	session_start();
	require_once("functions/errors.php");
	require_once("functions/security.php");

	if(isset($_POST["loginForm"])) 
	{
		login($_POST["username"], $_POST["password"]);
	}
?>
<!DOCTYPE html>
<html>
	<head>
<?php include("includes/standard_head.php"); ?>
		<title>Login</title>
	</head>
	<body>
<?php include("includes/navbar.php"); ?>
		<!-- Content start -->
		<div class="container">
			<div class="col-md-8 col-md-offset-2">
				<h1>Login</h1>
<?php	displayErrors(); ?>
				<form id="loginForm" method="post" enctype="multipart/form-data">
					<div class="form-group">
						<label>Username or Email:</label>
						<input type="text" maxlength="50" class="form-control" name="username" placeholder="Username/Email" required autofocus>
					</div>
					<div class="form-group">
						<label>Password:</label>
						<input type="password" maxlength="50" class="form-control" name="password" id="password" placeholder="Password" required>
					</div>
					<button class="btn btn-lg btn-primary btn-block" type="submit" name="loginForm">Submit</button>
				</form>
			</div>
		</div>
		<!-- Content end -->
<?php include("includes/standard_footer.php"); ?>
	</body>
</html>