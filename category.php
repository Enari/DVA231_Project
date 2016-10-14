<?php
	session_start();
	include("includes/dbconn.php");

	if (isset($_GET['id']))
	{
		$stmt = $conn->prepare('SELECT name FROM categories WHERE id = ?');
		$stmt->bind_param('i', $_GET['id']);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($name);


		if ($stmt->num_rows > 0)
		{
			$stmt->fetch();

			$stmt_2 = $conn->prepare('SELECT * FROM forums WHERE category = ? ORDER BY ordering');
			$stmt_2->bind_param('i', $_GET['id']);
			$stmt_2->execute();

			$result = $stmt_2->get_result();
			$stmt_2->store_result();
		}
		else
		{
			header("Location: index.php");
		}

		$stmt->free_result();
		$stmt->close();
	}
	else
	{
		header("Location: index.php");
	}

?>
<!DOCTYPE html>
<html>
	<head>
<?php include("includes/standard_head.php"); ?>
		<title><?php echo $name; ?></title>
	</head>
	<body>
<?php include("includes/navbar.php"); ?>		
	<!-- Content start -->
		<div class="container">
			<div class="row category">
				<h2 class="category-title"><?php echo $name; ?></h2>


				<?php

					if ($result->num_rows > 0)
					{
						while ($row = $result->fetch_assoc())
						{

							echo '<a href="forum.php?id=' . $row['id'] . '">';
							echo '<div class="col-lg-12 forum">';
							echo '<h4 class="forum-title">' . $row['name'] . '</h4>';
							echo '<p class="forum-desc">' . $row['description'] .'</p>';
							echo '</div>';
							echo '</a>';
						}
					}
					else
					{
						echo '<h3>Sorry! There\'s no forum in this category just yet!</h3>';
					}

					$stmt_2->free_result();
					$stmt_2->close();
				?>
			</div>
		</div>
		<!-- Content end -->
		<?php include("includes/standard_footer.php"); ?>
	</body>
</html>