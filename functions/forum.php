<?php
	require_once __DIR__.'/../includes/dbconn.php';
	require_once __DIR__.'/../functions/report.php';


	class category
	{
		public $id;
		public $name;
		public $sortOrder;

		function __construct($id)
		{
			global $conn;
				
			$stmt = $conn->prepare('SELECT id, name, ordering FROM categories WHERE id = ?');
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$stmt->store_result();
			if ($stmt->num_rows == 0)
				throw new Exception('Does not exist');		
			$stmt->bind_result($id, $name, $sortOrder);
			$stmt->fetch();
			$stmt->free_result();
			$stmt->close();

			$this->id = $id;
			$this->name = $name;
			$this->sortOrder = $sortOrder;
		}

		//returns an array with all categories objects
		public static function getAllCategories()
		{
			global $conn;
			$stmt = $conn->prepare('SELECT id FROM categories');
			$stmt->execute();
			$stmt->bind_result($id);
			while ($stmt->fetch()) 
			{
				$ids[] = $id;
			}
			$stmt->close();

			
			foreach($ids as $id) 
			{
				$categories[] = new category($id);
			}

			return $categories;
		}


		public static function newCategory($categoryName, $ordering)
		{
			global $conn;
			
			$stmt = $conn->prepare('INSERT INTO categories(name, ordering) VALUES (?,?)');
			$stmt->bind_param('si', $categoryName, $ordering);
			$stmt->execute();
			if(!empty($stmt->error))
				return false;
			$stmt->close();
			return true;
		}

		public function delete()
		{
			global $conn;
			$stmt = $conn->prepare('DELETE FROM categories WHERE id = ?');
			$stmt->bind_param('i', $this->id);
			$stmt->execute();
			if(!empty($stmt->error))
			{
				return false;
			}
			$stmt->close();
			return true;
		}
		
		public function getForums($limit=PHP_INT_MAX, $offset=0)
		{
			global $conn;
			$stmt = $conn->prepare('SELECT id FROM forums WHERE category = ? ORDER BY ordering LIMIT ? OFFSET ?');
			$stmt->bind_param('iii', $this->id, $limit, $offset);
			$stmt->execute();
			$stmt->bind_result($id);
			while ($stmt->fetch()) 
			{
				$ids[] = $id;
			}
			$stmt->close();

			if(empty($ids))
			{
				return false;
			}

			foreach($ids as $id) 
			{
				$forums[] = new forum($id);
			}

			return $forums;
		}

		public function getNumberOfForums()
		{
			global $conn;
			$stmt = $conn->prepare('SELECT COUNT(*) FROM forums WHERE category = ?');
			$stmt->bind_param('i', $this->id);
			$stmt->execute();
			$stmt->bind_result($count);
			$stmt->fetch();
			$stmt->close();

			return $count;
		}
	}

	class forum
	{
		public $id;
		public $name;
		public $description;
		public $category;
		public $sortOrder;
		public $guestAccess;

		function __construct($id)
		{
			global $conn;
			$this->views = 0;

			$stmt = $conn->prepare('SELECT id, name, description, category, ordering, guestAccess FROM forums WHERE id = ?');
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$stmt->store_result();
			if ($stmt->num_rows == 0)
				throw new Exception('Does not exist');		
			$stmt->bind_result($id, $name, $description, $category, $sortOrder, $guestAccess);
			$stmt->fetch();
			$stmt->free_result();
			$stmt->close();

			$this->id = $id;
			$this->name = $name;
			$this->description = $description;
			$this->category = $category;
			$this->sortOrder = $sortOrder;
			$this->guestAccess = $guestAccess;
		}

		public static function newForum($forumName, $description, $categoryID, $guestAccess, $ordering)
		{
			global $conn;

			if($categoryID === false)
				return false;

			$stmt = $conn->prepare('INSERT INTO forums(name, description, category, guestAccess, ordering) VALUES (?,?,?,?,?)');
			$stmt->bind_param('ssiii', $forumName, $description, $categoryID, $guestAccess, $ordering);
			$stmt->execute();
			if(!empty($stmt->error))
				return false;
			$stmt->close();
			return true;
		}

		public function delete()
		{
			global $conn;

			$stmt = $conn->prepare('DELETE FROM forums WHERE id = ?');
			$stmt->bind_param('i', $this->id);
			$stmt->execute();
			if(!empty($stmt->error))
				return false;
			
			$stmt->close();
			return true;
		}

		public function getPosts($limit=PHP_INT_MAX, $offset=0)
		{
			global $conn;
			$stmt = $conn->prepare('SELECT id FROM posts WHERE forum = ? ORDER BY created_at DESC LIMIT ? OFFSET ?');
			$stmt->bind_param('iii', $this->id, $limit, $offset);
			$stmt->execute();
			$stmt->bind_result($id);
			while ($stmt->fetch()) 
			{
				$ids[] = $id;
			}
			$stmt->close();

			if(!isset($ids))
				return;

			foreach($ids as $id) 
				$posts[] = new post($id);

			return $posts;
		}

		public function getReportedPosts()
		{
			global $conn;

			$stmt = $conn->prepare('SELECT id FROM reportedPosts WHERE forum=?');
			$stmt->bind_param('i', $this->id);
			$stmt->execute();
			if(!empty($stmt->error))
				return false;

			$stmt->bind_result($id);
			while ($stmt->fetch()) 
			{
				$ids[] = $id;
			}
			$stmt->close();

			if(empty($ids))
				return false;

			foreach($ids as $id) 
			{
				$postReports[] = new postReport($id);
			}

			return $postReports;
		}

		public function getReportedComments()
		{
			global $conn;

			$stmt = $conn->prepare('SELECT id FROM reportedComments WHERE forum=?');
			$stmt->bind_param('i', $this->id);
			$stmt->execute();
			if(!empty($stmt->error))
				return false;

			$stmt->bind_result($id);
			while ($stmt->fetch()) 
			{
				$ids[] = $id;
			}
			$stmt->close();

			if(empty($ids))
				return false;

			foreach($ids as $id) 
			{
				$commentReport[] = new commentReport($id);
			}

			return $commentReport;
		}

		public function getNumberOfPosts()
		{
			global $conn;
			$stmt = $conn->prepare('SELECT COUNT(*) FROM posts WHERE forum = ?');
			$stmt->bind_param('i', $this->id);
			$stmt->execute();
			$stmt->bind_result($count);
			$stmt->fetch();
			$stmt->close();

			return $count;
		}

		public function getNumberOfviews()
		{
			global $conn;

			$stmt = $conn->prepare('SELECT SUM(views) FROM posts Where forum = ?');
			$stmt->bind_param('i', $this->id);
			$stmt->execute();
			$stmt->bind_result($views);
			$stmt->fetch();
			$stmt->free_result();
			$stmt->close();

			if(!empty($views))				
				return $views;
			return 0;
		}
	}

	class post
	{
		public $id;
		public $creator;
		public $title;
		public $text;
		public $forum;
		public $views;
		public $createdAt;

		function __construct($id)
		{
			global $conn;
				
			$stmt = $conn->prepare('SELECT id, creator, title, text, forum, views, created_at FROM posts WHERE id = ?');
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$stmt->store_result();
			if ($stmt->num_rows == 0)
				throw new Exception('Does not exist');
			$stmt->bind_result($id, $creator, $title, $text, $forum, $views, $createdAt);
			$stmt->fetch();
			$stmt->free_result();
			$stmt->close();

			$this->id = $id;
			$this->creator = $creator;
			$this->title = $title;
			$this->text = $text;
			$this->forum = $forum;
			$this->views = $views;
			$this->createdAt = $createdAt;
		}

		public function delete()
		{
			global $conn;

			$stmt = $conn->prepare('DELETE FROM posts WHERE id = ?');
			$stmt->bind_param('i', $this->id);
			$stmt->execute();
			if(!empty($stmt->error))
				return false;

			$stmt->close();
			return true;
		}
		
		public function getComments($limit=PHP_INT_MAX, $offset=0)
		{
			global $conn;
			$stmt = $conn->prepare('SELECT id FROM comments WHERE postID = ? ORDER BY created_at LIMIT ? OFFSET ?');
			$stmt->bind_param('iii', $this->id, $limit, $offset);
			$stmt->execute();
			$stmt->bind_result($id);
			while ($stmt->fetch()) 
			{
				$ids[] = $id;
			}
			$stmt->close();

			if(!isset($ids))
				return;

			foreach($ids as $id) 
				$comments[] = new comment($id);

			return $comments;
		}

		public function getNumberOfComments()
		{
			global $conn;

			$stmt = $conn->prepare('SELECT COUNT(*) FROM comments WHERE postID = ?');
			$stmt->bind_param('i', $this->id);
			$stmt->execute();
			$stmt->bind_result($count);
			$stmt->fetch();
			$stmt->close();
			return $count;
		}

		public function getNumberOfviews()
		{
			return $this->views;
		}

		public function view()
		{
			global $conn;

			$stmt = $conn->prepare('UPDATE posts SET views = views + 1 WHERE id=?');
			$stmt->bind_param('i', $this->id);
			$stmt->execute();
		}
	}

	class comment
	{
		public $id;
		public $creator;
		public $post;
		public $text;
		public $createdAt;

		public function __construct($id)
		{
			global $conn;
				
			$stmt = $conn->prepare('SELECT id, userID, postID, text, created_at FROM comments WHERE id = ?');
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$stmt->store_result();
			if ($stmt->num_rows == 0)
				throw new Exception('Does not exist');	
			$stmt->bind_result($id, $creator, $post, $text, $createdAt);
			$stmt->fetch();
			$stmt->free_result();
			$stmt->close();

			$this->id = $id;
			$this->creator = $creator;
			$this->post = $post;
			$this->text = $text;
			$this->createdAt = $createdAt;
		}

		//TODO: Fix somehow to make objet selfdestruct...
		public function delete()
		{
			global $conn;
			$stmt = $conn->prepare('DELETE FROM comments WHERE id = ?');
			$stmt->bind_param('i', $this->id);
			$stmt->execute();
			if(!empty($stmt->error))
			{
				return false;
			}
			$stmt->close();
			return true;
		}
	}