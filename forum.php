<?php
	session_start();
	require_once "includes/dbconn.php";
	require_once "functions/get.php";
	require_once "functions/user.php";

	if (isset($_GET['id']))
	{
		$posts_per_page = 10;

		if (isset($_GET['page']))
		{
			$page = $_GET['page'];
		}
		else
		{
			$page = 1;
		}

		$posts = ($posts_per_page*$page)-10;

		$stmt = $conn->prepare('SELECT id, name FROM forums WHERE id = ?');
		$stmt->bind_param('i', $_GET['id']);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($id, $name);

		if ($stmt->num_rows > 0)
		{
			$stmt->fetch();				

			$stmt_2 = $conn->prepare('SELECT * FROM posts WHERE forum = ? ORDER BY created_at LIMIT 10 OFFSET ?');
			$stmt_2->bind_param('ii', $_GET['id'], $posts);
			$stmt_2->execute();

			$result = $stmt_2->get_result();
			$stmt_2->store_result();


			$getCount = $conn->prepare('SELECT DISTINCT COUNT(id) AS count FROM posts WHERE forum = ?');
			$getCount->bind_param('i', $_GET['id']);
			$getCount->execute();
			$getCount->store_result();
			$getCount->bind_result($count);
			$getCount -> fetch();
		}
		else
		{
			header("Location: index.php");
			die();
		}

		$stmt->free_result();
		$stmt->close();
	}
	else
	{
		header("Location: index.php");
		die();
	}

?>
<!DOCTYPE html>
<html>
	<head>
<?php include("includes/standard_head.php"); ?>
		<title>Forum</title>
	</head>
	<body>
<?php include("includes/navbar.php"); ?>		
		<!-- Content start -->
		<div class="container">
			<h1><?php echo $name; ?></h1>
			<div class="posts">

				<?php
					// Buttons for for posting, administrating and moderating.
					if (isAdmin())
					{
						echo '<div class="actions">';
						echo '<a href="post.php?new" class="btn btn-primary" role="button">New Post</a>';
						echo '<a href="moderate.php" class="btn btn-primary" role="button">Moderate</a>';
						echo '<a href="admin.php" class="btn btn-primary" role="button">Administrate</a>';
						echo '</div>';
					}
					elseif (idModerator($_GET['id']))
					{
						echo '<div class="actions">';
						echo '<a href="post.php?new" class="btn btn-primary" role="button">New Post</a>';
						echo '<a href="moderate.php" class="btn btn-primary" role="button">Moderate</a>';
						echo '</div>';
					}
					elseif (isLoggedIn())
					{
						echo '<div class="actions">';
						echo '<a href="post.php?new" class="btn btn-primary" role="button">New Post</a>';
						echo '</div>';
					}
						
					if ($result->num_rows > 0)
					{
						while ($row = $result->fetch_assoc())
						{
							echo '<div class="row">';
							echo '<a href="post.php?id=' . $row['id'] . '">';
							echo '<div class="col-lg-12 post">';
							echo '<div class="col-lg-10">';
							echo '<h3 class="post-title">' . $row['title'] . '</h3>';
							if (isAdminID($row['creator']))
							{
								echo '<p class="post-poster"><span class="admin">'.getUsername($row['creator']) . ' [A]</span></p>';
							}
							elseif (isModeratorID($row['creator'], $id))
							{
								echo '<p class="post-poster"><span class="mod">'.getUsername($row['creator']).' [M]</span></p>';
							}
							else
							{
								echo '<p class="post-poster">'.getUsername($row['creator']).'</p>';
							}
							echo '</div>';
							echo '<div class="col-lg-2">';
							echo '<p>Replies:<br>'.numberOfReplies($row['id'].'</p>');
							echo '</div>';
							echo '</div>';
							echo '</a>';
							echo '</div>';
						}
					}
					else
					{
						// Here we check if there's post in the forum, or if the user has tried goind to a page that doesn't have any results.

						if (!isset($page))
						{
							// We check if it's an empty forum.
							echo '<div class="alert alert-info">';
							echo '<h3><strong>Sorry!</strong> This page does not exist!</h3>';
							echo '</div>';
						}
						elseif ($page > ceil($count / 10))
						{
							// Here we check if the user has tried going to a page without any posts.
							echo '<div class="alert alert-info">';
							echo '<h3><strong>Sorry!</strong> There\'s no posts in this forum just yet!</h3>';
							echo '</div>';
						}
					}

					$stmt_2->free_result();
					$stmt_2->close();
				?>

			</div>
			<?php

				if ($count > 10)
				{
					echo '<div class="row">';
					echo '<ul class="pagination">';

					$pages = ceil($count / 10);

					for ($i = 1; $i <= $pages; $i++)
					{
						if ($i == $page)
							echo '<li class="active"><a href="forum.php?id='.$_GET['id'].'&page='.$i.'">'.$i.'</a></li>';
						else
							echo '<li><a href="forum.php?id='.$_GET['id'].'&page='.$i.'">'.$i.'</a></li>';
					}

					echo '</ul>';
					echo '</div>';
				}
				$getCount->free_result();
				$getCount->close();

			?>
		</div>
		<!-- Content end -->
<?php include("includes/standard_footer.php"); ?>
	</body>
</html>