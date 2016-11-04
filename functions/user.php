<?php
	require_once __DIR__.'/../includes/dbconn.php';
	require_once __DIR__.'/../functions/alerts.php';


	class user 
	{ 
		public $id;
		public $username;
		public $role;
		public $email;
		public $validEmail;
		public $banned;

		function __construct($id)
		{
			global $conn;
				
			$stmt = $conn->prepare('SELECT id, username, role, email, validEmail, banned FROM users WHERE id = ?');
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$stmt->store_result();		
			$stmt->bind_result($id, $username, $role, $email, $validEmail, $banned);
			$stmt->fetch();
			$stmt->free_result();
			$stmt->close();

			$this->id = $id;
			$this->username = $username;
			$this->role = $role;
			$this->email = $email;
			$this->validEmail = $validEmail;
			$this->banned = $banned;
		}
		public function isAdmin()
		{
			if($this->role === "admin")
				return true;
			return false;
		}
	}

	//======================================================================
	// currentUser START
	//======================================================================
	class currentUser 
	{ 
		public $id;
		public $username;
		public $role;
		public $email;
		public $validEmail;
		public $banned;
		public $loggedIn;

		function __construct()
		{
			global $conn;
			global $alerts;

			$this->loggedIn = false;

			if(isset($_SESSION['id']))
			{

				$stmt = $conn->prepare('SELECT id, username, role, email, validEmail, banned FROM users WHERE id = ?');
				$stmt->bind_param('i', $_SESSION['id']);
				$stmt->execute();
				$stmt->store_result();

				//if username does not exist in database, detroy session and redirect to index.
				if ($stmt->num_rows == 0)
				{
					unset($_SESSION['id']);
					session_destroy();
					header("Location: /index.php");
					die();
				}
				
				
				$stmt->bind_result($id, $username, $role, $email, $validEmail, $banned);
				$stmt->fetch();
				$stmt->free_result();
				$stmt->close();
				if(!$validEmail)
				{
					$alerts[] = new alert("danger", "Error:", "You have not verified your email. <a href='/verify.php?username=" . $username ."&email=" . $email ."'>Resend Verification Email</a>");
					unset($_SESSION['id']);
					session_destroy();
				}
				else if($banned)
				{
					$alerts[] = new alert("danger", "Error:", "You are banned");
					unset($_SESSION['id']);
					session_destroy();
				}
				else
				{
					$this->id = $id;
					$this->username = $username;
					$this->role = $role;
					$this->email = $email;
					$this->validEmail = $validEmail;
					$this->banned = $banned;
					$this->loggedIn = true;

					$friendRequests = $this->getFriendRequests();
					if(isset($friendRequests) && !empty($friendRequests))
					{
						foreach($friendRequests as $friendRequest) 
						{
							$alerts[] = new alert("info", "New Friend Reques:", "You have a new request from: ". getUsernameID($friendRequest) .'. <a href="#" onclick="javascript:acceptFriendRequest('. $friendRequest .');" data-dismiss="alert">Accept</a> / <a href="#" onclick="javascript:denyFriendRequest('. $friendRequest .');" data-dismiss="alert">Deny</a>'. '');
						}
					}
				}
			}
		}
		
		//-----------------------------------------------------
		// Security Start
		//-----------------------------------------------------

		public function setPassword($userID, $newPassword)
		{
			global $conn;
			//Hash new password
			$passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

			$stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
			$stmt->bind_param('si', $passwordHash, $userID);
			$stmt->execute();
			$stmt->close();
		}

		public function changePassword($oldPassword, $newPassword)
		{
			global $conn;
			global $alerts;

			//Validate old password
			$stmt = $conn->prepare('SELECT password FROM users WHERE id=?');
			$stmt->bind_param('i', $this->id);
			$stmt->execute();			
			$stmt->bind_result($passwordHash);
			$stmt->fetch();
			$stmt->close();
			
			if(password_verify($oldPassword , $passwordHash))
			{
				$this->setPassword($this->id, $newPassword);
				$alerts[] = new alert("success", "Sucess:", "You've sucessfully changed your password");
			}
			else
			{
				$alerts[] = new alert("danger", "Error:", "Wrong current password");
			}
		}

		public function login($usernameOrEmail, $password)
		{
			global $conn;
			global $alerts;

			$stmt = $conn->prepare('SELECT id, username, password, role, email, validEmail, banned FROM users WHERE username = ? OR email = ?');
			$stmt->bind_param('ss', $usernameOrEmail, $usernameOrEmail);
			$stmt->execute();
			
			if(!empty($stmt->error))
				$alerts[] = new alert("danger", "Error:", "SQL error: " . $stmt->error);
			
			$stmt->bind_result($id, $username, $passwordHash, $role, $email, $validEmail, $banned);
			$stmt->fetch();
			$stmt->free_result();
			$stmt->close();
			
			if(password_verify($password , $passwordHash))
			{
				$_SESSION["id"] = $id;
				$this->id = $id;
				$this->username = $username;
				$this->role = $role;
				$this->email = $email;
				$this->validEmail = $validEmail;
				$this->banned = $banned;
				$this->loggedIn = ture;
				return true;
			}
			else
			{
				$alerts[] = new alert("danger", "Error:", "Login Failed");
				return false;
			}
		}

		public function isAdmin()
		{
			if($this->role === "admin")
				return true;
			return false;
		}

		public function isLoggedIn()
		{
			return $this->loggedIn;
		}
		//-----------------------------------------------------
		// Security END
		//-----------------------------------------------------

		//-----------------------------------------------------
		// Friends functions Start
		//-----------------------------------------------------

		private function addFriend($userID)
		{
			global $conn;
			global $alerts;

			$stmt = $conn->prepare('INSERT INTO friends (userid, userid2, created_at) VALUES (?, ?, ?)');
			$stmt->bind_param('iii', $this->id, $userID, time());
			$stmt->execute();
			$stmt->store_result();
			if(!empty($stmt->error))
			{
				$alerts[] = new alert("danger", "Error:", "SQL error: " . $stmt->error);
				return false;
			}
			return true;
		}

		public function unFriend($userID)
		{
			global $conn;
			global $alerts;

			$stmt = $conn->prepare('DELETE FROM friends WHERE (userid=? AND userid2=?) OR (userid=? AND userid2=?)');
			$stmt->bind_param('iiii', $this->id, $userID, $userID, $this->id);
			$stmt->execute();
			if(!empty($stmt->error))
			{
				return false;
			}
			return true;
		}

		public function areFriendsWith($userID)
		{
			global $conn;
			$stmt = $conn->prepare('SELECT userid from friends WHERE (userid = ? AND userid2 = ?) OR (userid = ? AND userid2 = ?)');
			$stmt->bind_param('iiii', $this->id, $userID, $userID, $this->id);
			$stmt->execute();
			$stmt->store_result();
			if ($stmt->num_rows == 0)
				return false;
			else
				return true;
		}

		public function friendRequestExists($userID)
		{
			global $conn;

			$stmt = $conn->prepare('SELECT userid from friendRequests WHERE (userid = ? AND userid2 = ?) OR (userid = ? AND userid2 = ?)');
			$stmt->bind_param('iiii', $this->id, $userID, $userID, $this->id);
			$stmt->execute();
			$stmt->store_result();
			if ($stmt->num_rows == 0)
				return false;
			else
				return true;
		}

		public function getFriendRequests()
		{
			global $conn;

			$stmt = $conn->prepare('SELECT userid from friendRequests WHERE userid2 = ?');
			$stmt->bind_param('i', $this->id);
			$stmt->execute();
			$stmt->store_result();
			if ($stmt->num_rows == 0)
				return false;
			
			$stmt->bind_result($userID);
			while ($stmt->fetch()) 
			{
				$requestsFrom[] = $userID;
			}

			$stmt->free_result();
			$stmt->close();
			return $requestsFrom;
		}

		public function sendFriendRequest($userID)
		{
			global $conn;
			global $alerts;

			if(!userIDExists($userID))
			{
				$alerts[] = new alert("danger", "Error:", "That user does not exist");
				return false;
			}	
			if($this->friendRequestExists($userID))
			{
				$alerts[] = new alert("danger", "Error:", "You have already sent a friend request to: " . getUsernameID($userID) . ".");
				return false;
			}
			if($this->areFriendsWith($userID))
			{
				$alerts[] = new alert("danger", "Error:", "You are already friends with: " . getUsernameID($userID) . ".");
				return false;
			}

			$stmt = $conn->prepare('INSERT INTO friendRequests (userid, userid2, created_at) VALUES (?, ?, ?)');
			$stmt->bind_param('iii', $this->id, $userID, time());
			$stmt->execute();
			$stmt->store_result();
			if(!empty($stmt->error))
			{
				$alerts[] = new alert("danger", "Error:", "SQL error: " . $stmt->error);
				return false;
			}
			return true;
		}

		private function deleteFriendRequest($from, $to)
		{
			global $conn;
			$stmt = $conn->prepare('DELETE FROM friendRequests WHERE userid=? AND userid2=?');
			$stmt->bind_param('ii', $from, $to);
			$stmt->execute();
		}

		public function acceptFriendRequest($userID)
		{
			if(!$this->friendRequestExists($userID))
				return false;

			$this->addFriend($userID);
			$this->deleteFriendRequest($userID, $this->id);
			return true;
		}

		public function denyFriendRequest($userID)
		{
			if(!$this->friendRequestExists($userID))
				return false;

			$this->deleteFriendRequest($userID, $this->id);
			return true;
		}
		//-----------------------------------------------------
		// Friends functions Start
		//-----------------------------------------------------

		// Returns the number of unred messages,
		public function getNumberOfUnreadMessages()
		{
			global $conn;
			$stmt = $conn->prepare('SELECT COUNT(*) FROM messages WHERE to_user = ? AND isread = 0');
			$stmt->bind_param('i', $this->id);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($count);
			$stmt->fetch();
			$stmt->free_result();
			$stmt->close();

			return $count;
		}

		// Checks if a user is a moderator, returns true if he is, false if he's not logged in or not a moderator.
		public function isModerator ($forumID)
		{
			global $conn;
			$stmt = $conn->prepare('SELECT COUNT(*) from moderators WHERE userID = ? AND forumID = ?');
			$stmt->bind_param('ii', $this->id, $forumID);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($count);
			$stmt->fetch();
			$stmt->free_result();
			$stmt->close();

			if ($count == 0)
				return false;
			else
				return true;
			
		}
	} 
	//======================================================================
	// currentUser END
	//======================================================================


	// Checks if a user with the given ID exists
	function userIDExists ($userID)
	{
		global $conn;
		$stmt = $conn->prepare('SELECT id from users WHERE id = ?');
		$stmt->bind_param('i', $userID);
		$stmt->execute();
		$stmt->store_result();
		if ($stmt->num_rows == 0)
			return false;
		else
			return true;
	}

	// Checks if a user with the given username exists
	function usernameExists ($username)
	{
		global $conn;
		$stmt = $conn->prepare('SELECT id from users WHERE username = ?');
		$stmt->bind_param('s', $username);
		$stmt->execute();
		$stmt->store_result();
		if ($stmt->num_rows == 0)
			return false;
		else
			return true;
	}

	// Returns the username.
	function getUsernameID ($userID)
	{
		global $conn;
		$stmt = $conn->prepare('SELECT username FROM users WHERE id = ?');
		$stmt->bind_param('i', $userID);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($username);

		if($stmt->num_rows == 0)
			return false;

		$stmt->fetch();
		$stmt->free_result();
		$stmt->close();
		return $username;
	}

	// Returns the user ID.
	function getUserID ($usernameOrEmail)
	{
		global $conn;
		$stmt = $conn->prepare('SELECT id from users where username = ? OR email = ?');
		$stmt->bind_param('ss', $usernameOrEmail, $usernameOrEmail);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($id);

		if($stmt->num_rows == 0)
			return false;

		$stmt->fetch();
		$stmt->free_result();
		$stmt->close();

		return $id;
	}

	// Checks if a user with the given user ID is an admin.
	function isAdminID ($userID)
	{
		global $conn;
		$stmt = $conn->prepare('SELECT role FROM users WHERE id = ?');
		$stmt->bind_param('i', $userID);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($role);
		$stmt->fetch();
		$stmt->free_result();
		$stmt->close();
		if($role === "admin")
			return true;
		else
			return false;
	}

	// Checks if a user is a moderator, returns true if he is, false if he's not logged in or not a moderator.
	function isModerator ($forumID)
	{
		global $conn;
		if (!isset($_SESSION["id"]))
			return false;

		$stmt = $conn->prepare('SELECT COUNT(*) from moderators WHERE userID = ? AND forumID = ?');
		$stmt->bind_param('ii', $_SESSION["id"], $forumID);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($count);
		$stmt->fetch();
		$stmt->free_result();
		$stmt->close();

		if ($count == 0)
			return false;
		else
			return true;
	}

	// Checks if the user with the given user ID is moderator for the forum with the given forum ID.
	function isModeratorID ($userID, $forumID)
	{
		global $conn;
		$stmt = $conn->prepare('SELECT COUNT(*) from moderators WHERE userID = ? AND forumID = ?');
		$stmt->bind_param('ii', $userID, $forumID);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($count);
		$stmt->fetch();
		$stmt->free_result();
		$stmt->close();

		if ($count == 0)
			return false;
		else
			return true;
	}

	// Returns an array of all the forums that the user with the given user ID is moderator for.
	function isModeratorFor ($userID)
	{
		return $array;
	}

?>