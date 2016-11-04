<?php

	require_once("includes/init.php");
	
?>

<!DOCTYPE html>
<html>
	<head>
		<?php include("includes/standard_head.php"); ?>
		<title>Forum</title>

		<script type="text/javascript">
			var last = Math.floor(Date.now() / 1000);

			function update() 
			{
				var xmlhttp = new XMLHttpRequest();
				xmlhttp.onreadystatechange = function() {
					if (this.readyState == 4 && this.status == 200 && this.responseText !== false) {
						window.last = Math.floor(Date.now() / 1000);
						$("#liveContainer").prepend(this.responseText);
					}
				};
				xmlhttp.open("GET", "updateFlow.php?t=" + window.last, true);
				xmlhttp.send();
				
			}

			setInterval(update, 3000);
		</script>
	</head>
	<body>
		<?php include("includes/navbar.php"); ?>

		<div class="container" id="container">
			<h1>Flow</h1>

			<div id="liveContainer">
			</div>
		</div>

		<?php require_once("includes/standard_footer.php"); ?>
	</body>
</html>