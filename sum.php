<?php
include "index.php";

$token = "573869217:AAHa-kFyMQJ-TXV_wsLDlnQqw9hs5Wu2ujU";
$bot = new \TelegramBot\Api\Client($token);

sumTasks($bot);

?>