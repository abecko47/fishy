
<textarea rows="20" cols="70" name="notification" form="notification-form"></textarea><br><br>	
<form type='post' action="notify.php" id="notification-form">
	<input type="submit">
</form>
<?php
include "index.php";

$token = "573869217:AAHa-kFyMQJ-TXV_wsLDlnQqw9hs5Wu2ujU";
$bot = new \TelegramBot\Api\Client($token);

if(isset($_GET['notification'])){
	$notification = $_GET['notification'];
	notify($bot, $notification);
}

?>