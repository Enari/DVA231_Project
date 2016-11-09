<?php	

	require_once("../includes/init.php");
	require_once("../functions/admin.php");

	//Kill if users is not admin
	if(!$currentUser->isAdmin())
	{
		header("Location: /index.php");
		die();
	}

	//-----------------------------------------------------
	//Delete Category
	//-----------------------------------------------------
	if (isset($_GET['action']) && isset($_GET['id']))
	{
		if($_GET['action'] === "delete")
			(new category($_GET['id']))->delete();
	}

	//-----------------------------------------------------
	//New Category
	//-----------------------------------------------------
	if(isset($_POST["newCategory"])) 
	{
		category::newCategory($_POST["categoryName"], $_POST["ordering"]);
	}

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
		<div class="container">
<?php displayAlerts(); ?>
			<div class="row">
<?php include("../includes/admin_menu.php"); ?>
				<!-- Categories Start -->
				<div id="pannelAdminCategories" class="panel panel-default">
					<div class="panel-heading">
						Categories
						<button  type="button" class="btn btn-xs btn-primary pull-right" data-toggle="collapse" data-target="#newCategoryWell">New Category</button>
					</div>
					<div class="panel-body">
						<div id="newCategoryWell" class="well well-sm collapse">
							<form id="newCategory" method="post" enctype="multipart/form-data">
								<div class="row">
									<div class="col-sm-7">
										<div class="input-group">
											<span class="input-group-addon">Name: </span>
											<input type="text" name="categoryName" class="form-control" placeholder="Category name">
										</div>
									</div>
									<div class="col-sm-2">
										<div class="input-group">
											<span class="input-group-addon">Order:</span>
											<input type="text" name="ordering" class="form-control" size="1" placeholder="1">
										</div>
									</div>
									<div class="col-sm-3">
											<button type="submit" name="newCategory" class="btn btn-sm btn-primary">Create Category</button>
											<button type="button" class="btn btn-sm btn-default" data-toggle="collapse" data-target="#newCategoryWell">Cancel</button>
										</span>
									</div>
								</div>
							</form>
						</div>
						<!-- List existing categories start-->
						<div style="overflow-x: auto;">
							<table class="table">
								<thead>
										<tr>
											<th>ID</th>
											<th>Category</th>
											<th>Sort Order</th>
											<th>#of Forums</th>
											<th></th>
											</tr>
								</thead>
								<tbody>
<?php
	$categories = getCategories();
	foreach ($categories as $category) 
	{
		echo '<tr>';
		echo '<td>'. $category->id .'</td>';
		echo '<td>'. $category->name .'</td>';
		echo '<td>'. $category->sortOrder .'</td>';
		echo '<td>'. $category->getNumberOfForums() .'</td>';
		echo '<td><a href="#", data-href="?action=delete&id='. $category->id .'" data-toggle="modal" data-target="#confirm-delete" class="btn btn-xs btn-danger pull-right'; 
		if($category->getNumberOfForums() !== 0)
			{ 
				echo ' disabled';
			}
		echo '">Delete</a><a href="editcategory.php?id='. $category->id .'" class="btn btn-xs btn-primary pull-right">Edit</a></td>';
		echo '</tr>';
	}
?>
								</tbody>
							</table>
						</div>
						<!-- List existing categories end-->
						</form>
					</div>
					<!-- Categories End -->
					<!-- Modal confirmation start -->
					<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
						<div class="modal-dialog">
								<div class="modal-content">
										<div class="modal-header">
												<h3>Warning!<h3>
										</div>
										<div class="modal-body">
												You're about to delete a category this can not be undone.
										</div>
										<div class="modal-footer">
												<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
												<a class="btn btn-danger btn-ok">Delete</a>
										</div>
								</div>
						</div>
					</div>
					<!-- Modal confirmation End -->
			</div>
		</div>
		<!-- Content end -->
<?php include("../includes/standard_footer.php"); ?>
		<script src="/js/custom/admin-menu.js"></script> 
		<script>
			$('#confirm-delete').on('show.bs.modal', function(e) {
				$(this).find('.btn-ok').attr('href', $(e.relatedTarget).data('href'));
			});
		</script>
	</body>
</html>