<?php

	require_once("includes/init.php");

	if (isset($_GET['new']) && isset($_GET['forum']))
	{
		if ($forumName = getForumName($_GET['forum']))
		{
			$newPost = true;
			$forum = true;
		}
		else
		{
			$newPost = true;
			$forum = false;
		}
	}
	elseif (isset($_POST['comment']))
	{
		$postedComment = $_POST['comment'];
		if (strlen($postedComment) > 1 && strlen($postedComment) <= 5000)
		{
			
		}
	}
	elseif (isset($_GET['id']))
	{
		$comments_per_page = 9;

		if (isset($_GET['page']))
		{
			$page = $_GET['page'];
		}
		else
		{
			$page = 1;
		}
		$commentsOffset = ($comments_per_page*$page)-$comments_per_page;

		$stmt = $conn->prepare('SELECT * FROM posts WHERE id = ?');
		$stmt->bind_param('i', $_GET['id']);
		$stmt->execute();
		$result = $stmt->get_result();
		$stmt->store_result();
		

		if ($result->num_rows == 0)
			header('Location: index.php');


		$post = $result->fetch_array();

		$repliesSTMT = $conn->prepare('SELECT * FROM comments WHERE postID = ? ORDER BY created_at LIMIT ? OFFSET ?');
		$repliesSTMT->bind_param('iii', $post['id'], $comments_per_page, $commentsOffset);
		$repliesSTMT->execute();
		$comments = $repliesSTMT->get_result();
		$repliesSTMT->store_result();

		$getCount = $conn->prepare('SELECT COUNT(id) AS count FROM posts WHERE forum = ?');
		$getCount->bind_param('i', $_GET['id']);
		$getCount->execute();
		$getCount->bind_result($count);
		$getCount->fetch();

		$getCount->free_result();
		$getCount->close();
		$repliesSTMT->free_result();
		$stmt->free_result();
		$stmt->close();
		$repliesSTMT->close();

	}
	else
	{
		header('Location: index.php');
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
			<h2 class="post-title">
			<?php 
				$categoryID = forumBelongsTo($post['forum']);
				echo '<a href="category.php?id='.$categoryID.'">'.getCategoryName($categoryID).'</a> / ';
				echo '<a href="forum.php?id='.$post['forum'].'">'.getForumName($post['forum']).'</a> / ';
				echo $post['title']; 
				?>
			</h2>
			<div class="row post-post">
				<div class="col-lg-2 post-profile">
					<h4><?php echo getUsernameID($post['creator']); ?></h4>
					<img src="img/cat.jpg" alt="Profile picture">
				</div>
				<div class="col-lg-10 post-text">
					<p><?php echo $post['text']; ?></p>
				</div>
			</div>

			<?php
			if ($comments->num_rows > 0)
			{
				while ($comment = $comments->fetch_assoc())
				{
					echo '<div class="row post-reply">';
					echo '<div class="col-lg-2 post-profile">';
					echo '<h4>'.getUsernameID($comment['userID']).'</h4>';
					echo '<img src="img/cat.jpg" alt="Profile picture">';
					echo '</div>';
					echo '<div class="col-lg-10 post-text">';
					echo '<p>'.$comment['text'].'</p>';
					echo '</div>';
					echo '</div>';
				}
			}


		if ($count > $comments_per_page)
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
			$pages = ceil($count / $comments_per_page);

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


			<?php
			if (isLoggedIn())
			{
			?>
				<div class="row post-reply-form">
					<h3>Reply</h3>
					<form action="post.php" method="post">
						<textarea name="comment" maxlength="5000" required></textarea>
						<br>
						<input type="submit">
					</form>
				</div>
			<?php
			}
			?>

		</div>
		<!-- Content end -->
<?php include("includes/standard_footer.php"); ?>
	</body>
</html>