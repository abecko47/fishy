
<?php
$bot->command('greet', function ($message) use ($bot) {
	greet($bot);
});

$bot->command('sum', function ($message) use ($bot) {
	sumTasks($bot);
});



$bot->on(
  function($Update) use ($bot)
  {
    $message = $Update->getMessage();
    $user_text = $message->getText();
    $chat_id = $message->getChat()->getId();
    $updateId = $Update->getUpdateId();
    $fishy = new Fishy();
	$fishy -> connect_db(HOST, USER, PASS, DB);

    $cmd = $fishy -> getCommand($chat_id);

    if(strcasecmp($cmd['command_name'], "add") == 0) {
    	if($cmd['progress'] == 0){
    		$user_text = $fishy -> checkdate($user_text, $chat_id);
    		$commandObj -> date = $user_text;
    		$json = json_encode($commandObj);

    		$fishy -> updateCommand($chat_id, "add", 1, $json);
    		$bot->sendMessage($chat_id, "Отлично! Теперь введи себе задание, например \"искупать кота\": ");
    	}
    	else if($cmd['progress'] == 1){
    		//$user_text = $fishy -> checkdate($user_text, $chat_id);
    		$commandBody = json_decode($cmd['json']);
    		$date = $commandBody->date;

    		$fishy -> updateCommand($chat_id, "", 0, "");
    		$answer = $fishy -> addTodo($date, $user_text, $chat_id);

    		$bot->sendMessage($chat_id, $answer);
    	}
    }

    if(strcasecmp($cmd['command_name'], "list") == 0) {
    	if($cmd['progress'] == 0){
    		$user_text = $fishy -> checkdate($user_text, $chat_id);
    		$fishy -> updateCommand($chat_id, "", 0, "");

    		$list = $fishy -> getTodo($chat_id, $user_text);
    		$len = count($list);

		    if($len > 0){
				$answer = getList($len, $list, $user_text);
		    } else $answer = "На выбранную дату еще нет заданий!\n\nЧтобы добавить задание воспользуйтесь командой /add";
    		$bot->sendMessage($chat_id, $answer, "Markdown");
    	}
    }

    if(strcasecmp($cmd['command_name'], "remove") == 0) {
    	if($cmd['progress'] == 0){

    		$user_text = $fishy -> checkdate($user_text, $chat_id);
    		$list = $fishy -> getTodo($chat_id, $user_text);
    		$len = count($list);

		    if($len > 0){
		    	$user_text = $fishy -> checkdate($user_text, $chat_id);
    			$commandObj -> date = $user_text;
    			$commandObj -> list = $list;
    			$json = json_encode($commandObj);

		    	$fishy -> updateCommand($chat_id, "remove", 1, $json);
				$answer = getList($len, $list, $user_text) . "\nВпиши номер таска (только цифру), который хочешь убрать. Когда закончишь, напиши команду /done";
		    } else {
		    	$fishy -> updateCommand($chat_id, "", 0, "");
		    	$answer = "На выбранную дату еще нет заданий!\n\nЧтобы добавить задание воспользуйтесь командой /add";
		    }
    		$bot->sendMessage($chat_id, $answer, "Markdown");
    	}

    	if($cmd['progress'] == 1){

    		
    		$commandBody = json_decode($cmd['json']);

    		$date = $commandBody->date;
    		$date = $fishy -> checkdate($date, $chat_id);

    		$answer = $fishy -> removeTodo($date, $commandBody->list[$user_text-1]->description, $chat_id);
    		array_splice($commandBody->list, $user_text-1, 1);
    		$json = json_encode($commandBody);
    		$fishy -> updateCommand($chat_id, "remove", 1, $json);

    		$list = $commandBody->list;
    		$len = count($list);
    		$answer .= getListJSON($len, $list, $date);

    		$answer .= "\nВпиши номер таска (только цифру), который хочешь убрать. Когда закончишь, напиши команду /done\n";

    		$bot->sendMessage($chat_id, $answer, "Markdown");
    	}
    }      
    
  	if(strcasecmp($cmd['command_name'], "complete") == 0) {
  		if($cmd['progress'] == 0){

  		$commandBody = json_decode($cmd['json']);
  		$date = $fishy -> checkdate("today", $chat_id);

  		$answer = $fishy -> completeTask($date, $commandBody->list[$user_text-1]->description, $chat_id);

  		$commandBody -> list[$user_text-1] -> status = 1;
    	$json = json_encode($commandBody);
    	$fishy -> updateCommand($chat_id, "complete", 0, $json);

    	$list = $commandBody->list;
    	$len = count($list);
    	$answer .= getListJSON($len, $list, $date);

    	$answer .= "\nВпиши номер таска (только цифру), который ты выполнил. Когда закончишь, напиши команду /done\n";

    	$bot->sendMessage($chat_id, $answer, "Markdown");
  		}
  	}

  	if(strcasecmp($cmd['command_name'], "gmt") == 0) {
  		if($cmd['progress'] == 0){

  		if(preg_match("[^(\+|-)\d]", $user_text)){
  			$answer = $fishy -> setGmt($chat_id, $user_text);
  		}
  		
  		else {
  			$answer = "Введённое вами значение не соответствует требованиям.\nВпиши свой GMT, например \"+2\".";
  		} 

    	$bot->sendMessage($chat_id, $answer, "Markdown");
  		}
  	}

  	if(strcasecmp($cmd['command_name'], "greet") == 0) {
  		if($cmd['progress'] == 0){

  		if((int)$user_text >= 0 && (int)$user_text <= 23){
  			$answer = $fishy -> setGreet($chat_id, $user_text);
  		}
  		
  		else {
  			$answer = "Введённое вами значение не соответствует требованиям.\nВпиши только цифру (0-23), например \"8\".";
  		} 

    	$bot->sendMessage($chat_id, $answer, "Markdown");
  		}
  	}

  	if(strcasecmp($cmd['command_name'], "sum") == 0) {
  		if($cmd['progress'] == 0){

  		if((int)$user_text >= 0 && (int)$user_text <= 23){
  			$answer = $fishy -> setSum($chat_id, $user_text);
  		}
  		
  		else {
  			$answer = "Введённое вами значение не соответствует требованиям.\nВпиши только цифру (0-23), например \"8\".";
  		} 

    	$bot->sendMessage($chat_id, $answer, "Markdown");
  		}
  	}

},
function($message) use ($name)
{
  return true; // когда тут true - команда проходит
});
?>