<?php
	/*
	TODO:
		- Use the function getForumName.
	*/

	require_once("includes/init.php");

	if (!isset($_GET['id']))
	{
		header("Location: index.php");
		die();
	}

	// Number of posts we want to display per page.
	$posts_per_page = 10;

	// If the GET variable page isn't set, we just send them to the first page.
	if (isset($_GET['page']))
	{
		$page = $_GET['page'];
	}
	else
	{
		$page = 1;
	}

	/*
	 * The variable posts are basically the offset. 
	 * If we are on page 2 and are showing 10 results per page, we don't want to get the first 10.
	 * Then we want to start from 11.
	 */
	$posts = ($posts_per_page*$page)-$posts_per_page;

	//-----------------------------------------------------
	// Get the id and name for the current forum.
	//-----------------------------------------------------
	$stmt = $conn->prepare('SELECT id, name FROM forums WHERE id = ?');
	$stmt->bind_param('i', $_GET['id']);
	$stmt->execute();
	$stmt->store_result();
	$stmt->bind_result($id, $name);

	// If it doesn't exist we send the back to index.
	if ($stmt->num_rows === 0)
	{	
		header("Location: index.php");
		die();
	}

	$stmt->fetch();
	$stmt->free_result();
	$stmt->close();

	//-----------------------------------------------------
	// stmt_2 is responsible for getting all of the posts in this forum.
	//-----------------------------------------------------
	$stmt_2 = $conn->prepare('SELECT * FROM posts WHERE forum = ? ORDER BY created_at DESC LIMIT ? OFFSET ?');
	$stmt_2->bind_param('iii', $_GET['id'], $posts_per_page, $posts);
	$stmt_2->execute();
	$result = $stmt_2->get_result();
	$stmt_2->store_result();
	$stmt_2->close();

	//-----------------------------------------------------
	// getCount gets the total amount of posts in this forum and puts it in the count varible.
	//-----------------------------------------------------
	$getCount = $conn->prepare('SELECT COUNT(id) AS count FROM posts WHERE forum = ?');
	$getCount->bind_param('i', $_GET['id']);
	$getCount->execute();
	$getCount->bind_result($count);
	$getCount->fetch();
	$getCount->free_result();
	$getCount->close();

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
			<h3>
			<?php
			// This should use the function getForumName when that function is done!
			$categoryID = forumBelongsTo($id);

			 echo '<a href="category.php?id='.$categoryID.'" class="category-title">'.getCategoryName($categoryID).'</a> / '.$name; 
			 ?>
			 </h3>

			<div class="posts">
<?php
	// Buttons for for posting, administrating and moderating.
	if ($currentUser->isAdmin())
	{
		echo '<div class="actions">';
		echo '<a href="newPost.php?forum='.$id.'" class="btn btn-default" role="button">New Post</a>';
		echo '<a href="moderate.php" class="btn btn-default" role="button">Moderate</a>';
		echo '<a href="admin/editforum.php?id='. $id . '" class="btn btn-default" role="button">Administrate</a>';
		echo '</div>';
	}
	elseif (isModerator($_GET['id']))
	{
		echo '<div class="actions">';
		echo '<a href="newPost.php?forum='.$id.'" class="btn btn-default" role="button">New Post</a>';
		echo '<a href="moderate.php" class="btn btn-default" role="button">Moderate</a>';
		echo '</div>';
	}
	elseif ($currentUser->isLoggedIn())
	{
		echo '<div class="actions">';
		echo '<a href="newPost.php?forum='.$id.'" class="btn btn-default" role="button">New Post</a>';
		echo '</div>';
	}
		
	// Checks to see if there's actually any posts.
	if ($result->num_rows > 0)
	{
		// Outputs every post.
		while ($row = $result->fetch_assoc())
		{
			echo '<div class="row">';
			echo '<a href="post.php?id=' . $row['id'] . '">';
			echo '<div class="col-lg-12 post">';
			echo '<div class="col-lg-10">';
			echo '<h4 class="post-title">' . $row['title'] . '</h4>';

			// Nice admin and moderator colors for the "creator" text. Blue is for moderator and red is for admin.
			if (isAdminID($row['creator']))
			{
				echo '<p class="post-poster"><span class="admin">'.getUsernameID($row['creator']) . ' [A]</span></p>';
			}
			elseif (isModeratorID($row['creator'], $id))
			{
				echo '<p class="post-poster"><span class="mod">'.getUsernameID($row['creator']).' [M]</span></p>';
			}
			else
			{
				echo '<p class="post-poster">'.getUsernameID($row['creator']).'</p>';
			}
			echo '<span class="post-time"> - '.date('H:i d/m/y', $row['created_at']).'</span>';
			echo '</div>';
			echo '<div class="col-lg-2">';
			echo '<p>Replies:<br>'.numberOfReplies($row['id']).'</p>';
			echo '</div>';
			echo '</div>';
			echo '</a>';
			echo '</div>';
		}
	}
	else
	{
		// Here we check if there's post in the forum, or if the user has tried goind to a page that doesn't have any results.
		if ($count == 0)
		{
			// We check if it's an empty forum.
			echo '<div class="alert alert-info">';
			echo '<h3><strong>Sorry!</strong> There\'s no posts in this forum just yet!</h3>';
			echo '</div>';

		}
		elseif ($page > ceil($count / $posts_per_page))
		{
			// Here we check if the user has tried going to a page without any posts.
			echo '<div class="alert alert-info">';
			echo '<h3><strong>Sorry!</strong> This page does not exist!</h3>';
			echo '</div>';
		}
	}
	$result->free_result();
?>
			</div>
<?php

	// Checks to see if the number of posts exceeds the number of posts we allow per page. In that case we will need pagination.
	if ($count > $posts_per_page)
	{
		echo '<nav aria-label="Page navigation">';
		echo '<div class="row">';
		echo '<ul class="pagination">';
		if ($page == 1)
		{
			echo '<li class="page-item disabled">';
			echo '<a class="page-link" href="#" aria-label="Previous">';
		}
		else 
		{
			echo '<li class="page-item">';
			echo '<a class="page-link" href="forum.php?id='.$_GET['id'].'&page='.($page-1).'" aria-label="Previous">';
		}
		echo '<span aria-hidden="true">&laquo;</span>';
		echo '</a></li>';

		// Number of pages we need, rounded up.
		$pages = ceil($count / $posts_per_page);

		for ($i = 1; $i <= $pages; $i++)
		{
			// Makes the current page active.
			if ($i == $page)
				echo '<li class="page-item active"><a class="page-link" href="forum.php?id='.$_GET['id'].'&page='.$i.'">'.$i.'</a></li>';
			else
				echo '<li class="page-item"><a class="page-link" href="forum.php?id='.$_GET['id'].'&page='.$i.'">'.$i.'</a></li>';
		}
		if ($page == $pages)
		{
			echo '<li class="page-item disabled">';
			echo '<a class="page-link" href="#" aria-label="Next">';
		}
		else 
		{
			echo '<li class="page-item">';
			echo '<a class="page-link" href="forum.php?id='.$_GET['id'].'&page='.($page+1).'" aria-label="Next">';
		}		
		echo '<span aria-hidden="true">&raquo;</span>';
		echo '</a></li>';
		echo '</ul>';
		echo '</div>';
		echo '</nav>';
	}
?>
		</div>
		<!-- Content end -->
		<?php include("includes/standard_footer.php"); ?>
	</body>
</html>