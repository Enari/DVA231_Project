<?php	
	session_start();

	require_once '../includes/dbconn.php';
	require_once "../functions/user.php";
	require_once "../functions/admin.php";

	//Kill if users is not admin
	if(!isAdmin())
	{
		header("Location: /index.php");
		die();
	}

	//-----------------------------------------------------
	//Delete user
	//-----------------------------------------------------
	if (isset($_GET['action']) && isset($_GET['id']))
	{
		if($_GET['action'] === "delete")
			deleteUser($_GET['id']);
	}

	//-----------------------------------------------------
	//Get Users
	//-----------------------------------------------------
	$stmt = $conn->prepare('SELECT id, username, role, banned FROM users');
	$stmt->execute();
	$stmt->store_result();
	$stmt->bind_result($id, $username, $role, $banned);

?>
<!DOCTYPE html>
<html>
	<head>
<?php include("../includes/standard_head.php"); ?>
		<title>Admin</title>
	</head>
	<body>
<?php include("../includes/navbar.php"); ?>
		<!-- Content start -->
		<div class="container-fluid"">
			<div class="row">
<?php include("../includes/admin_menu.php"); ?>
				<div class="col-sm-10">
<?php 
if(isset($error))
	displayErrors($error); 
?>
<!-- Users Start -->
					<div id="pannelAdminUsers" class="panel panel-default">
						<div class="panel-heading">
							Users
						</div>
						<div class="panel-body">
							<!-- List existing users start-->
							<table class="table">
						    <thead>
						        <tr>
						        	<th>ID</th>
						        	<th>Username</th>
						        	<th>Role</th>
						        	<th>Banned</th>
						        	</tr>
						    </thead>
						    <tbody>
<?php
	while($stmt->fetch())
	{
?>
									<tr>
										<td><?php echo $id; ?></td>
										<td><?php echo $username; ?></td>
										<td><?php echo $role; ?></td>
										<td><?php
										if($banned === 0)
											echo "No"; 
										else
											echo "yes";
										?></td>
										<td><span class="input-group-btn"><a href="?action=delete&id=<?php echo $id; ?>" class="btn btn-xs btn-danger pull-right">Delete</a><a href="?action=edit&id=<?php echo $id; ?>" class="btn btn-xs btn-success pull-right">Edit</a></span></td>
									</tr>
<?php
	}
	$stmt->free_result();
	$stmt->close();
?>
								</tbody>
							</table>
							<!-- List existing users end-->
						</div>
					</div>
					<!-- Users End -->
				</div>
		</div>
		<!-- Content end -->
<?php include("../includes/standard_footer.php"); ?>
	</body>
</html>