<?php 
require_once "vendor/autoload.php";
include "config.php";

$token = "573869217:AAHa-kFyMQJ-TXV_wsLDlnQqw9hs5Wu2ujU";
$bot = new \TelegramBot\Api\Client($token);

$fishy = new Fishy();
$fishy -> connect_db(HOST, USER, PASS, DB);

function getList($len, $list, $date) {
		

	    $answer = "*Ваши задания на дату $date:*\n\n";
	    for ($i=0; $i < $len; $i++) {

	    	if($list[$i]['status'] == 1){
				$smile = "\xE2\x9C\x85"; //галочка
			} else{
				$smile = "\xF0\x9F\x94\xA5";//огонь
			}

	    	$answer .= $i+1 .") " . $list[$i]['description'] . " " . $smile ."\n";
	    }    	
	    return $answer;
}

function getListJSON($len, $list, $date) {
	    $answer = "*Ваши задания на дату $date:*\n\n";
	    for ($i=0; $i < $len; $i++) {

	    	if($list[$i]->status == 1){
				$smile = "\xE2\x9C\x85"; //галочка
			} else{
				$smile = "\xF0\x9F\x94\xA5";//огонь
			}

	    	$answer .= $i+1 .") " . $list[$i]->description . " " . $smile . "\n";
	    }    	
	    return $answer;
}

function countDone($list) {
		$len = count($list);
	    
	    $done = 0;
	    for ($i=0; $i < $len; $i++) {
	    	if($list[$i]['status'] == 1){
				$done++;
			} 
	    }    	
	    $answer = "\nСегодня ты выполнил $done/$len целей.";
	    if($done == $len) {
	    	$answer .= "\nТы отлично поработал! Можешь собой гордиться! Сегодня ты лучший. \xF0\x9F\x92\xAA";
	    } 
	    else if($done == 0) {
	    	$answer .= "\nКак-то слабо... К сожалению, сегодня ты не выполнил ни одной из поставленных целей. Работай над собой! \xF0\x9F\x92\xA9 \nЧтобы переместить свои невыполненные цели на завтра воспользуйся командой /movetomorrow.";
	    } else  {
	    	$answer .= "\nМолодец! Вижу ты старался, хоть и не выполнил всех целей. \xE2\x9D\xA4 \nЧтобы переместить свои невыполненные цели на завтра воспользуйся командой /movetomorrow.";
	    }
	    return $answer;
}

function greet($bot) {
	$fishy = new Fishy();
	$fishy -> connect_db(HOST, USER, PASS, DB);

	$list = $fishy -> getUsers();
	$len = count($list);

	for ($i=0; $i < $len; $i++) { 
			$answer = "Доброе утро! 🙉\nГотов к продуктивному дню сегодня?\n\n";
			$chat_id = $list[$i]['chat_id'];

			$date = "today";
		    $date = $fishy -> checkdate($date, $chat_id);
		    $user_list = $fishy -> getTodo($chat_id, $date);

		    $hour = $fishy -> getHour($chat_id);
		    $settings = $fishy -> getSettings($chat_id);
		    $greet = $settings['greet_time'];

		    if($hour != $greet)
		    continue;

		    $user_len = count($user_list);

		    if($user_len > 0){
				$answer .= getList($user_len, $user_list, $date);
		    } else $answer .= "На выбранную дату еще нет заданий!\n\nЧтобы добавить задание воспользуйтесь командой /add";

		    if($fishy -> checkYesterday($date)){
		    	$answer .= "\n\n*У вас есть невыполненные вчерашние задания!*\nСамое время переместить их с помощью команды /movetoday";
		    }

		    try {
				$bot->sendMessage($chat_id, $answer, "Markdown");
			} catch (Exception $e) {
				continue;
			}
	}

}

function sumTasks($bot) {
	$fishy = new Fishy();
	$fishy -> connect_db(HOST, USER, PASS, DB);

	$list = $fishy -> getUsers();
	$len = count($list);

	for ($i=0; $i < $len; $i++) { 
			$answer = "Добрый вечер! 🌚\nДавай посмотрим как ты сегодня поработал!\n\n";
			$chat_id = $list[$i]['chat_id'];

			$date = "today";
		    $date = $fishy -> checkdate($date, $chat_id);
		    $user_list = $fishy -> getTodo($chat_id, $date);
		    $user_len = count($user_list);

		    $hour = $fishy -> getHour($chat_id);
		    $settings = $fishy -> getSettings($chat_id);
		    $sum = $settings['sum_time'];

		    if($hour != $sum)
		    continue;

		    if($user_len > 0){
				$answer .= getList($user_len, $user_list, $date);
				$answer .= countDone($user_list);
		    } else $answer .= "Сегодня я не нашла у тебя целей. А жаль, ведь TODO-list это очень полезная вещь.\nДобавить задание себе можно с помощью /add";


		    try {
				$bot->sendMessage($chat_id, $answer, "Markdown");
			} catch (Exception $e) {
				continue;
			}
	}

}

function notify($bot, $notification) {
	$fishy = new Fishy();
	$fishy -> connect_db(HOST, USER, PASS, DB);

	$list = $fishy -> getUsers();
	$len = count($list);

	for ($i=0; $i < $len; $i++) { 
			$chat_id = $list[$i]['chat_id'];
		try {
			$bot->sendMessage($chat_id, $notification, "Markdown");
		} catch (Exception $e) {
			continue;
		}
	}

}
// $output = json_decode(file_get_contents('php://input'), TRUE);
// $chat_id = $output['message']['chat']['id'];
// $message = $output['message']['text'];
// $callback_query = $output['callback_query'];
// $data = $callback_query['data'];
// $message_id = ['callback_query']['message']['message_id'];
// $chat_id_in = $callback_query['message']['chat']['id'];
// $bot->sendMessage($chat_id_in, $data);


$bot->on(function($update) use ($bot, $callback_loc, $find_command){
	$callback = $update->getCallbackQuery();
	$message = $callback->getMessage();
	$chat_id = $message->getChat()->getId();
	$data = $callback->getData();



	$message_id = $message -> getMessageId();


	$fishy = new Fishy();
	$fishy -> connect_db(HOST, USER, PASS, DB);

	$data = json_decode($data);

	$command = $data -> command;
	


	if(strcasecmp($command, "add") == 0){
		$date = $data -> date;
    	$date = $fishy -> checkdate($date, $chat_id);
    	$commandObj -> date = $date;
    	$json = json_encode($commandObj);

    	$fishy -> updateCommand($chat_id, "add", 0, $json);

    	$bot->editMessageText($chat_id, $message_id, "Отлично! Теперь введи себе задание, например \"искупать кота\": ",  "Markdown");
    	$bot->answerCallbackQuery($callback->getId());
	}

	if(strcasecmp($command, "list") == 0){
		$date = $data -> date;
    	$date = $fishy -> checkdate($date, $chat_id);

    	$list = $fishy -> getTodo($chat_id, $date);
    	$len = count($list);

		if($len > 0){
			$answer = getList($len, $list, $date);
		} else $answer = "На выбранную дату еще нет заданий!\n\nЧтобы добавить задание воспользуйтесь командой /add";

    	$bot->editMessageText($chat_id, $message_id, $answer,  "Markdown");
    	$bot->answerCallbackQuery($callback->getId());
	}

	if(strcasecmp($command, "rm") == 0){
		$date = $data -> date;
    	$date = $fishy -> checkdate($date, $chat_id);
    	$commandObj -> date = $date;
    	$json = json_encode($commandObj);

    	$fishy -> updateCommand($chat_id, "rm", 0, $json);

    	$list = $fishy -> getTodo($chat_id, $date);
    	$len = count($list);

		if($len > 0){
    		$commandObj -> list = $list;
    		$json = json_encode($commandObj);

		    $fishy -> updateCommand($chat_id, "remove", 1, $json);

			$keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
		            [
		                [
		                    ['text' => '1', 'callback_data' => '{"command":"num", "num":1, "block":"rm"}'],
		                    ['text' => '2', 'callback_data' => '{"command":"num", "num":2, "block":"rm"}'],
		                    ['text' => '3', 'callback_data' => '{"command":"num", "num":3, "block":"rm"}'],
		                    ['text' => '4', 'callback_data' => '{"command":"num", "num":4, "block":"rm"}'],
		                    ['text' => '5', 'callback_data' => '{"command":"num", "num":5, "block":"rm"}'],
		                    ['text' => '6', 'callback_data' => '{"command":"num", "num":6, "block":"rm"}']
		                ],
		                [
		                    ['text' => '⬅️', 'callback_data' => "{\"command\":\"prev_list\", \"block\":\"rm\", \"prev\":0}"],
		                    ['text' => '➡️', 'callback_data' => "{\"command\":\"next_list\", \"block\":\"rm\", \"next\":7}"]
		                ],
		                [
		                    ['text' => 'Готово', 'callback_data' => '{"command":"ready"}']
		                ], 
		            ]
		    );

			$answer = getList($len, $list, $date) . "\nВыбери номер, который хочешь удалить, из списка, переключай цифры с помощью стрелок.\nКогда закончишь, нажми *Готово*\n";
			$bot->editMessageText($chat_id, $message_id, $answer,  "Markdown", false, $keyboard);
	    	$bot->answerCallbackQuery($callback->getId());

		    } else {
		    	$fishy -> updateCommand($chat_id, "", 0, "");
		    	$answer = "На выбранную дату еще нет заданий!\n\nЧтобы добавить задание воспользуйтесь командой /add";
		    	$bot->editMessageText($chat_id, $message_id, $answer,  "Markdown");
		    	$bot->answerCallbackQuery($callback->getId());
		    }

	}

	if(strcasecmp($command, "num") == 0){
		$num = $data -> num;
		$cmd = $fishy -> getCommand($chat_id);

    	$commandBody = json_decode($cmd['json']);

    	$date = $commandBody->date;
    	$date = $fishy -> checkdate($date, $chat_id);


    	$block = $data -> block;
    	if(strcasecmp($block, "rm") == 0){
	    	$answer = $fishy -> removeTodo($date, $commandBody->list[$num-1]->description, $chat_id);
	    	array_splice($commandBody->list, $num-1, 1);
	    	$json = json_encode($commandBody);
	    	$fishy -> updateCommand($chat_id, "remove", 1, $json);

	    	$list = $commandBody->list;
	    	$len = count($list);
	    	$answer .= getListJSON($len, $list, $date);
			$keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
			        [
			            [
			                ['text' => '1', 'callback_data' => '{"command":"num", "num":1, "block":"rm"}'],
			                ['text' => '2', 'callback_data' => '{"command":"num", "num":2, "block":"rm"}'],
			                ['text' => '3', 'callback_data' => '{"command":"num", "num":3, "block":"rm"}'],
			                ['text' => '4', 'callback_data' => '{"command":"num", "num":4, "block":"rm"}'],
			                ['text' => '5', 'callback_data' => '{"command":"num", "num":5, "block":"rm"}'],
			                ['text' => '6', 'callback_data' => '{"command":"num", "num":6, "block":"rm"}']
			            ],
			            [
			                ['text' => '⬅️', 'callback_data' => "{\"command\":\"prev_list\", \"block\":\"rm\", \"prev\":0}"],
			                ['text' => '➡️', 'callback_data' => "{\"command\":\"next_list\", \"block\":\"rm\", \"next\":7}"]
			            ],
			            [
			                ['text' => 'Готово', 'callback_data' => '{"command":"ready"}']
			            ], 
			        ]
			);

			$answer .= "\nВыбери номер, который хочешь удалить, из списка, переключай цифры с помощью стрелок.\nКогда закончишь, нажми *Готово*\n";    	
    	}

    	if(strcasecmp($block, "cp") == 0){
	  		$answer = $fishy -> completeTask($date, $commandBody->list[$num-1]->description, $chat_id);

	  		$commandBody -> list[$num-1] -> status = 1;
	    	$json = json_encode($commandBody);
	    	$fishy -> updateCommand($chat_id, "complete", 0, $json);

	    	$list = $commandBody->list;
	    	$len = count($list);
	    	$answer .= getListJSON($len, $list, $date);
			$keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
			        [
			            [
			                ['text' => '1', 'callback_data' => '{"command":"num", "num":1, "block":"cp"}'],
			                ['text' => '2', 'callback_data' => '{"command":"num", "num":2, "block":"cp"}'],
			                ['text' => '3', 'callback_data' => '{"command":"num", "num":3, "block":"cp"}'],
			                ['text' => '4', 'callback_data' => '{"command":"num", "num":4, "block":"cp"}'],
			                ['text' => '5', 'callback_data' => '{"command":"num", "num":5, "block":"cp"}'],
			                ['text' => '6', 'callback_data' => '{"command":"num", "num":6, "block":"cp"}']
			            ],
			            [
			                ['text' => '⬅️', 'callback_data' => "{\"command\":\"prev_list\", \"block\":\"cp\", \"prev\":0}"],
			                ['text' => '➡️', 'callback_data' => "{\"command\":\"next_list\", \"block\":\"cp\", \"next\":7}"]
			            ],
			            [
			                ['text' => 'Готово', 'callback_data' => '{"command":"ready"}']
			            ], 
			        ]
			);

			$answer .= "\nВыбери номер таска, _который ты выполнил сегодня_, из списка, переключай цифры с помощью стрелок.\nКогда закончишь, нажми *Готово*\n";    	
    	}

		$bot->editMessageText($chat_id, $message_id, $answer,  "Markdown", false, $keyboard);
	    $bot->answerCallbackQuery($callback->getId());
	}

if(strcasecmp($command, "prev_list") == 0){
		$prev = $data -> prev;
		
		$block = $data -> block;
		if($prev == 0){
			$bot->answerCallbackQuery($callback->getId());
			return;
		}

		$cmd = $fishy -> getCommand($chat_id);

    	$commandBody = json_decode($cmd['json']);

    	$date = $commandBody->date;
    	$date = $fishy -> checkdate($date, $chat_id);

    	$list = $commandBody->list;
    	$len = count($list);


		if(strcasecmp($block, "rm") == 0){
    	$answer .= getListJSON($len, $list, $date) . "\nВыбери номер, который хочешь удалить, из списка, переключай цифры с помощью стрелок.\n Когда закончишь, нажми *Готово*\n";
			$i = $prev;
			//echo $num[0] =  "{\"command\":\"num\", \"num\":$i-6, \"block\":\"rm\"}";
		$keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
		        [
		            [
		                ['text' => ($i-5), 'callback_data' => "{\"command\":\"num\", \"num\":".($i-5).", \"block\":\"rm\"}"],
		                ['text' => ($i-4), 'callback_data' => "{\"command\":\"num\", \"num\":".($i-4).", \"block\":\"rm\"}"],
		                ['text' => ($i-3), 'callback_data' => "{\"command\":\"num\", \"num\":".($i-3).", \"block\":\"rm\"}"],
		                ['text' => ($i-2), 'callback_data' => "{\"command\":\"num\", \"num\":".($i-2).", \"block\":\"rm\"}"],
		                ['text' => ($i-1), 'callback_data' => "{\"command\":\"num\", \"num\":".($i-1).", \"block\":\"rm\"}"],
		                ['text' => ($i), 'callback_data' => "{\"command\":\"num\", \"num\":".($i).", \"block\":\"rm\"}"]
		            ],
		            [
		                ['text' => '⬅️', 'callback_data' => "{\"command\":\"prev_list\", \"block\":\"rm\", \"prev\":".($i-6)."}"],
		                ['text' => '➡️', 'callback_data' => "{\"command\":\"next_list\", \"block\":\"rm\", \"next\":".($i+1)."}"]
		            ],
		            [
		                ['text' => 'Готово', 'callback_data' => '{"command":"ready"}']
		            ], 
		        ]
		);			
		}

		if(strcasecmp($block, "cp") == 0){
    	$answer .= getListJSON($len, $list, $date) . "\nВыбери номер, _который ты выполнил сегодня_, из списка, переключай цифры с помощью стрелок.\n Когда закончишь, нажми *Готово*\n";
			$i = $prev;
			//echo $num[0] =  "{\"command\":\"num\", \"num\":$i-6, \"block\":\"rm\"}";
		$keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
		        [
		            [
		                ['text' => ($i-5), 'callback_data' => "{\"command\":\"num\", \"num\":".($i-5).", \"block\":\"cp\"}"],
		                ['text' => ($i-4), 'callback_data' => "{\"command\":\"num\", \"num\":".($i-4).", \"block\":\"cp\"}"],
		                ['text' => ($i-3), 'callback_data' => "{\"command\":\"num\", \"num\":".($i-2).", \"block\":\"cp\"}"],
		                ['text' => ($i-2), 'callback_data' => "{\"command\":\"num\", \"num\":".($i-2).", \"block\":\"cp\"}"],
		                ['text' => ($i-1), 'callback_data' => "{\"command\":\"num\", \"num\":".($i-1).", \"block\":\"cp\"}"],
		                ['text' => ($i), 'callback_data' => "{\"command\":\"num\", \"num\":".($i).", \"block\":\"cp\"}"]
		            ],
		            [
		                ['text' => '⬅️', 'callback_data' => "{\"command\":\"prev_list\", \"block\":\"cp\", \"prev\":".($i-6)."}"],
		                ['text' => '➡️', 'callback_data' => "{\"command\":\"next_list\", \"block\":\"cp\", \"next\":".($i+1)."}"]
		            ],
		            [
		                ['text' => 'Готово', 'callback_data' => '{"command":"ready"}']
		            ], 
		        ]
		);			
		}

		$bot->editMessageText($chat_id, $message_id, $answer,  "Markdown", false, $keyboard);
	    $bot->answerCallbackQuery($callback->getId());
	}

if(strcasecmp($command, "next_list") == 0){
		$next = $data -> next;
		
		$block = $data -> block;

		$cmd = $fishy -> getCommand($chat_id);

    	$commandBody = json_decode($cmd['json']);

    	$date = $commandBody->date;
    	$date = $fishy -> checkdate($date, $chat_id);

    	$list = $commandBody->list;
    	$len = count($list);


		if(strcasecmp($block, "rm") == 0){
    	$answer .= getListJSON($len, $list, $date) . "\nВыбери номер, который хочешь удалить, из списка, переключай цифры с помощью стрелок.\nКогда закончишь, нажми *Готово*\n";			
			$i = $next;
		$keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
		        [
		            [
		                ['text' => ($i), 'callback_data' => '{"command":"num", "num":'.($i).', "block":"rm"}'],
		                ['text' => ($i+1), 'callback_data' => '{"command":"num", "num":'.($i+1).', "block":"rm"}'],
		                ['text' => ($i+2), 'callback_data' => '{"command":"num", "num":'.($i+2).', "block":"rm"}'],
		                ['text' => ($i+3), 'callback_data' => '{"command":"num", "num":'.($i+3).', "block":"rm"}'],
		                ['text' => ($i+4), 'callback_data' => '{"command":"num", "num":'.($i+4).', "block":"rm"}'],
		                ['text' => ($i+5), 'callback_data' => '{"command":"num", "num":'.($i+5).', "block":"rm"}']
		            ],
		            [
		                ['text' => '⬅️', 'callback_data' => "{\"command\":\"prev_list\", \"block\":\"rm\", \"prev\":".($i-1)."}"],
		                ['text' => '➡️', 'callback_data' => "{\"command\":\"next_list\", \"block\":\"rm\", \"next\":".($i+6)."}"]
		            ],
		            [
		                ['text' => 'Готово', 'callback_data' => '{"command":"ready"}']
		            ], 
		        ]
		);			
		}

		if(strcasecmp($block, "cp") == 0){
    	$answer .= getListJSON($len, $list, $date) . "\nВыбери номер, _который ты выполнил сегодня_, из списка, переключай цифры с помощью стрелок.\nКогда закончишь, нажми *Готово*\n";			
			$i = $next;
		$keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
		        [
		            [
		                ['text' => ($i), 'callback_data' => '{"command":"num", "num":'.($i).', "block":"cp"}'],
		                ['text' => ($i+1), 'callback_data' => '{"command":"num", "num":'.($i+1).', "block":"cp"}'],
		                ['text' => ($i+2), 'callback_data' => '{"command":"num", "num":'.($i+2).', "block":"cp"}'],
		                ['text' => ($i+3), 'callback_data' => '{"command":"num", "num":'.($i+3).', "block":"cp"}'],
		                ['text' => ($i+4), 'callback_data' => '{"command":"num", "num":'.($i+4).', "block":"cp"}'],
		                ['text' => ($i+5), 'callback_data' => '{"command":"num", "num":'.($i+5).', "block":"cp"}']
		            ],
		            [
		                ['text' => '⬅️', 'callback_data' => "{\"command\":\"prev_list\", \"block\":\"cp\", \"prev\":".($i-1)."}"],
		                ['text' => '➡️', 'callback_data' => "{\"command\":\"next_list\", \"block\":\"cp\", \"next\":".($i+6)."}"]
		            ],
		            [
		                ['text' => 'Готово', 'callback_data' => '{"command":"ready"}']
		            ], 
		        ]
		);			
		}

		$bot->editMessageText($chat_id, $message_id, $answer,  "Markdown", false, $keyboard);
	    $bot->answerCallbackQuery($callback->getId());
	}

	if(strcasecmp($command, "next") == 0 || strcasecmp($command, "prev") == 0){


		$date = $data -> date;
		$date = $fishy -> checkdate($date, $chat_id);

    	$block = $data -> block;

    	if(strcasecmp($command, "prev") == 0){
    		$date = $fishy -> addDay($date, -1);
    	} else $date = $fishy -> addDay($date, 1);
		
    	
    	if(strcasecmp($block, "add") == 0){
	    	$fishy -> updateCommand($chat_id, "", 0, "");

			$keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
		            [
		                [
		                    ['text' => 'Сегодня', 'callback_data' => '{"command":"add", "date":"today"}'],['text' => 'Завтра', 'callback_data' => '{"command":"add", "date":"tomorrow"}'],
		                ], 
		                [
		                    ['text' => '⬅️', 'callback_data' => "{\"command\":\"prev\", \"date\":\"$date\", \"block\":\"add\"}"],['text' => $date, 'callback_data' => "{\"command\":\"add\", \"date\":\"$date\"}"],
		                    ['text' => '➡️', 'callback_data' => "{\"command\":\"next\", \"date\":\"$date\", \"block\":\"add\"}"]
		                ]
		            ]
		        );
			$text = "На какую дату запишем таск?";
		}

    	if(strcasecmp($block, "list") == 0){

			$keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
		            [
		                [
		                    ['text' => 'Сегодня', 'callback_data' => '{"command":"list", "date":"today"}'],['text' => 'Завтра', 'callback_data' => '{"command":"list", "date":"tomorrow"}'],
		                ], 
		                [
		                    ['text' => '⬅️', 'callback_data' => "{\"command\":\"prev\", \"date\":\"$date\", \"block\":\"list\"}"],['text' => $date, 'callback_data' => "{\"command\":\"list\", \"date\":\"$date\"}"],
		                    ['text' => '➡️', 'callback_data' => "{\"command\":\"next\", \"date\":\"$date\", \"block\":\"list\"}"]
		                ]
		            ]
		        );
			$text = "На какую дату будем смотреть список заданий?";
		}

    	if(strcasecmp($block, "rm") == 0){

			$keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
		            [
		                [
		                    ['text' => 'Сегодня', 'callback_data' => '{"command":"rm", "date":"today"}'],['text' => 'Завтра', 'callback_data' => '{"command":"rm", "date":"tomorrow"}'],
		                ], 
		                [
		                    ['text' => '⬅️', 'callback_data' => "{\"command\":\"prev\", \"date\":\"$date\", \"block\":\"rm\"}"],['text' => $date, 'callback_data' => "{\"command\":\"rm\", \"date\":\"$date\"}"],
		                    ['text' => '➡️', 'callback_data' => "{\"command\":\"next\", \"date\":\"$date\", \"block\":\"rm\"}"]
		                ]
		            ]
		        );
			$text = "С какой даты мне удалить задание?";
		}

		$bot->editMessageText($chat_id, $message_id, $text, null, false, $keyboard);   		
    	$bot->answerCallbackQuery($callback->getId());

	}	

	if(strcasecmp($command, "ready") == 0){
    	$fishy -> updateCommand($chat_id, "", 0, "");

    	$bot->editMessageText($chat_id, $message_id, "Дело сделано.",  "Markdown");
    	$bot->answerCallbackQuery($callback->getId());
	}

	



}, function($update){
	$callback = $update->getCallbackQuery();
	if (is_null($callback) || !strlen($callback->getData()))
	return false;
	return true;
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
    		//$user_text = $fishy -> checkdate($user_text, $chat_id);
    		$commandBody = json_decode($cmd['json']);
    		$date = $commandBody->date;

    		$fishy -> updateCommand($chat_id, "", 0, "");
    		$answer = $fishy -> addTodo($date, $user_text, $chat_id);

    		$bot->sendMessage($chat_id, $answer);
    	}
	}


  	if(strcasecmp($cmd['command_name'], "gmt") == 0) {
  		if($cmd['progress'] == 0){

  		if(preg_match("[^(\+|-)\d]", $user_text)){
  			$answer = $fishy -> setGmt($chat_id, $user_text);
  			$fishy -> updateCommand($chat_id, "", 0, "");
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
  			$fishy -> updateCommand($chat_id, "", 0, "");
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
  			$fishy -> updateCommand($chat_id, "", 0, "");
  		}
  		
  		else {
  			$answer = "Введённое вами значение не соответствует требованиям.\nВпиши только цифру (0-23), например \"8\".";
  		} 
  		
    	$bot->sendMessage($chat_id, $answer, "Markdown");
  		}
  	}
	//$bot->sendMessage($chat_id, $user_text);
},
function($message) use ($name)
{
  return true; // когда тут true - команда проходит
});


//sumTasks($bot);
//greet($bot);

// команда для start
$bot->command('start', function ($message) use ($bot) {
	$fishy = new Fishy();
	$fishy -> connect_db(HOST, USER, PASS, DB);

    $answer = "Привет, я рыбка Fishy!\nЯ помогу тебе организовать свой день, сделать напоминание и помочь с запланированными событиями. Тут ты сможешь организовать todo-list, обозначать выполненные задание, и переносить невыполненные на потом. Каждое утро я буду тебя приветствовать и говорить твои ежедневные таски, а каждый вечер подводить итоги.\nВведи /help, чтобы узнать как мною пользоваться!\n\nТак же рекомендую сразу поставить свой часовой пояс (посмотреть можно в настройках) /settings\n[Как узнать свой часовой пояс?](https://greenwichmeantime.com/time-gadgets/time-zone-converter/)
";
    $chat_id = $message->getChat()->getId();
    $name = $message->getChat()->getUsername();

    $fishy -> addUser($name, $chat_id);
    $bot->sendMessage($chat_id, $answer, "Markdown");
});



$bot->command('help', function ($message) use ($bot) {
	$answer = "/add - добавить цель на день\n/list - список Ваших тасков\n\n/complete - пометить сегодняшнее задание выполненным\n/remove - удалить задание\n\n";
    $answer .= "/today - список целей на сегодня\n/tomorrow - список целей на завтра\n\n/settings - открыть окно настроек\n\n/movetomorrow - переместить свои невыполненные цели на завтра (не злоупотребляй)\n\n🔥 - невыполненные задания\n✅ - выполненные задания\n\nВсе даты в формате дд.мм.гггг, например 09.09.2018\n\n";
    $bot->sendMessage($message->getChat()->getId(), $answer);
});

$bot->command('settings', function ($message) use ($bot) {
	$answer = "/gmt - настроить свой gmt (по умолчанию GMT+3)\n\n/greet - настроить время приветствия бота (по умолчанию 7:00)\n/sum - настроить время, когда бот будет подводить итоги. (по умолчанию 22:00)\n\nВсё время должно быть указано в твоём часовом поясе.";
    $bot->sendMessage($message->getChat()->getId(), $answer);
});

$bot->command('add', function ($message) use ($bot) {

	$fishy = new Fishy();
	$fishy -> connect_db(HOST, USER, PASS, DB);

	$chat_id = $message->getChat()->getId();
	$fishy -> updateCommand($chat_id, "add", 0, "{}");

	$after_tomorrow = $fishy -> checkdate("after_tomorrow", $chat_id);


	$keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
            [
                [
                    ['text' => 'Сегодня', 'callback_data' => '{"command":"add", "date":"today"}'],['text' => 'Завтра', 'callback_data' => '{"command":"add", "date":"tomorrow"}'],
                ], 
                [
                    ['text' => '⬅️', 'callback_data' => "{\"command\":\"prev\", \"date\":\"$after_tomorrow\", \"block\":\"add\"}"],['text' => $after_tomorrow, 'callback_data' => "{\"command\":\"add\", \"date\":\"$after_tomorrow\"}"],
                    ['text' => '➡️', 'callback_data' => "{\"command\":\"next\", \"date\":\"$after_tomorrow\", \"block\":\"add\"}"]
                ]
            ]
        );

    $bot->sendMessage($chat_id, "На какую дату запишем таск?", null, false, null, $keyboard);
});


$bot->command('list', function ($message) use ($bot) {
	$fishy = new Fishy();
	$fishy -> connect_db(HOST, USER, PASS, DB);

	$chat_id = $message->getChat()->getId();

	$after_tomorrow = $fishy->checkdate("after_tomorrow", $chat_id);
	$keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
            [
                [
                    ['text' => 'Сегодня', 'callback_data' => '{"command":"list", "date":"today"}'],['text' => 'Завтра', 'callback_data' => '{"command":"list", "date":"tomorrow"}'],
                ], 
                [
                    ['text' => '⬅️', 'callback_data' => "{\"command\":\"prev\", \"date\":\"$after_tomorrow\", \"block\":\"list\"}"],['text' => $after_tomorrow, 'callback_data' => "{\"command\":\"list\", \"date\":\"$after_tomorrow\"}"],
                    ['text' => '➡️', 'callback_data' => "{\"command\":\"next\", \"date\":\"$after_tomorrow\", \"block\":\"list\"}"]
                ]
            ]
        );

    $bot->sendMessage($chat_id, "На какую дату будем смотреть список заданий?", null, false, null, $keyboard);
});

$bot->command('remove', function ($message) use ($bot) {
	$fishy = new Fishy();
	$fishy -> connect_db(HOST, USER, PASS, DB);

	$chat_id = $message->getChat()->getId();
	$fishy -> updateCommand($chat_id, "remove", 0, "{}");

	$after_tomorrow = $fishy->checkdate("after_tomorrow", $chat_id);
	$keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
            [
                [
                    ['text' => 'Сегодня', 'callback_data' => '{"command":"rm", "date":"today"}'],['text' => 'Завтра', 'callback_data' => '{"command":"rm", "date":"tomorrow"}'],
                ], 
                [
                    ['text' => '⬅️', 'callback_data' => "{\"command\":\"prev\", \"date\":\"$after_tomorrow\", \"block\":\"rm\"}"],['text' => $after_tomorrow, 'callback_data' => "{\"command\":\"rm\", \"date\":\"$after_tomorrow\"}"],
                    ['text' => '➡️', 'callback_data' => "{\"command\":\"next\", \"date\":\"$after_tomorrow\", \"block\":\"rm\"}"]
                ]
            ]
        );

    $bot->sendMessage($chat_id, "С какой даты мне удалить задание?", null, false, null, $keyboard);
});

$bot->command('complete', function ($message) use ($bot) {
	$fishy = new Fishy();
	$fishy -> connect_db(HOST, USER, PASS, DB);

	$chat_id = $message->getChat()->getId();

	// $date = "today";
 //    $date = $fishy -> checkdate($date, $chat_id);
 //    $list = $fishy -> getTodo($message->getChat()->getId(), $date);
 //    $len = count($list);

 //    if($len > 0){
	// 	$answer = "Впиши номер таска (только цифру), который ты выполнил. Когда закончишь, напиши команду /done\n" . getList($len, $list, $date);
 //    } else {
 //    	$answer = "На выбранную дату еще нет заданий!\n\nЧтобы добавить задание воспользуйтесь командой /add";
 //    	return;
 //    }

 //    $commandObj -> list = $list;
 //    $json = json_encode($commandObj);    
 //    $fishy -> updateCommand($chat_id, "complete", 0, $json);

		$date = "today";
    	$date = $fishy -> checkdate($date, $chat_id);
    	$commandObj -> date = $date;
    	$json = json_encode($commandObj);

    	//$fishy -> updateCommand($chat_id, "complete", 0, $json);

    	$list = $fishy -> getTodo($chat_id, $date);
    	$len = count($list);

		if($len > 0){
    		$commandObj -> list = $list;
    		$json = json_encode($commandObj);

		    $fishy -> updateCommand($chat_id, "complete", 1, $json);

			$keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
		            [
		                [
		                    ['text' => '1', 'callback_data' => '{"command":"num", "num":1, "block":"cp"}'],
		                    ['text' => '2', 'callback_data' => '{"command":"num", "num":2, "block":"cp"}'],
		                    ['text' => '3', 'callback_data' => '{"command":"num", "num":3, "block":"cp"}'],
		                    ['text' => '4', 'callback_data' => '{"command":"num", "num":4, "block":"cp"}'],
		                    ['text' => '5', 'callback_data' => '{"command":"num", "num":5, "block":"cp"}'],
		                    ['text' => '6', 'callback_data' => '{"command":"num", "num":6, "block":"cp"}']
		                ],
		                [
		                    ['text' => '⬅️', 'callback_data' => "{\"command\":\"prev_list\", \"block\":\"cp\", \"prev\":0}"],
		                    ['text' => '➡️', 'callback_data' => "{\"command\":\"next_list\", \"block\":\"cp\", \"next\":7}"]
		                ],
		                [
		                    ['text' => 'Готово', 'callback_data' => '{"command":"ready"}']
		                ], 
		            ]
		    );

			$answer = getList($len, $list, $date) . "\nВыбери номер, _который ты выполнил сегодня_, из списка, переключай цифры с помощью стрелок.\nКогда закончишь, нажми *Готово*\n";


		    } else {
		    	$fishy -> updateCommand($chat_id, "", 0, "");
		    	$answer = "На выбранную дату еще нет заданий!\n\nЧтобы добавить задание воспользуйтесь командой /add";

		    }

    		$bot->sendMessage($chat_id, $answer, "Markdown", false, null, $keyboard);
});

$bot->command('done', function ($message) use ($bot) {
	$fishy = new Fishy();
	$fishy -> connect_db(HOST, USER, PASS, DB);

	$chat_id = $message->getChat()->getId();
	$fishy -> updateCommand($chat_id, "", 0, "");

    $bot->sendMessage($chat_id, "Дело сделано.");
});

$bot->command('movetomorrow', function ($message) use ($bot) {
	$fishy = new Fishy();
	$fishy -> connect_db(HOST, USER, PASS, DB);

	$chat_id = $message->getChat()->getId();
	$answer = $fishy -> moveTomorrow($chat_id);
	$answer .= "Ниже я выведу список твоих заданий на завтра: \n\n";

	$date = "tomorrow";
    $date = $fishy -> checkdate($date, $chat_id);
    $list = $fishy -> getTodo($chat_id, $date);
    $len = count($list);

    if($len > 0){
		$answer .= getList($len, $list, $date);
    } else $answer .= "На выбранную дату еще нет заданий!\n\nЧтобы добавить задание воспользуйтесь командой /add";
    $bot->sendMessage($chat_id, $answer, "Markdown");
});

$bot->command('movetoday', function ($message) use ($bot) {
	$fishy = new Fishy();
	$fishy -> connect_db(HOST, USER, PASS, DB);

	$chat_id = $message->getChat()->getId();
	$answer = $fishy -> moveToday($chat_id);
	$answer .= "Ниже я выведу список твоих заданий на сегодня: \n\n";

	$date = "today";
    $date = $fishy -> checkdate($date, $chat_id);
    $list = $fishy -> getTodo($chat_id, $date);
    $len = count($list);

    if($len > 0){
		$answer .= getList($len, $list, $date);
    } else $answer .= "На выбранную дату еще нет заданий!\n\nЧтобы добавить задание воспользуйтесь командой /add";
    $bot->sendMessage($chat_id, $answer, "Markdown");
});

$bot->command('today', function ($message) use ($bot) {
	$fishy = new Fishy();
	$fishy -> connect_db(HOST, USER, PASS, DB);
    	
	$date = "today";
    $date = $fishy -> checkdate($date, $message->getChat()->getId());
    $list = $fishy -> getTodo($message->getChat()->getId(), $date);
    $len = count($list);

    if($len > 0){
		$answer = getList($len, $list, $date);
    } else $answer = "На выбранную дату еще нет заданий!\n\nЧтобы добавить задание воспользуйтесь командой /add";

    $bot->sendMessage($message->getChat()->getId(), $answer, "Markdown");
});

$bot->command('tomorrow', function ($message) use ($bot) {
	$fishy = new Fishy();
	$fishy -> connect_db(HOST, USER, PASS, DB);
    	
	$date = "tomorrow";
    $date = $fishy -> checkdate($date, $message->getChat()->getId());
    $list = $fishy -> getTodo($message->getChat()->getId(), $date);
    $len = count($list);

    if($len > 0){
		$answer = getList($len, $list, $date);
    } else $answer = "На выбранную дату еще нет заданий!\n\nЧтобы добавить задание воспользуйтесь командой /add";

    $bot->sendMessage($message->getChat()->getId(), $answer, "Markdown");
});

$bot->command('gmt', function ($message) use ($bot) {
	$fishy = new Fishy();
	$fishy -> connect_db(HOST, USER, PASS, DB);

	$chat_id = $message->getChat()->getId();

	$answer = "Впиши свой GMT, например \"+2\".";

    $fishy -> updateCommand($chat_id, "gmt", 0, "{}");

    $bot->sendMessage($chat_id, $answer, "Markdown");
});

$bot->command('greet', function ($message) use ($bot) {
	$fishy = new Fishy();
	$fishy -> connect_db(HOST, USER, PASS, DB);

	$chat_id = $message->getChat()->getId();

	$answer = "Впиши время в которое я буду тебя приветствовать с утра (или не с утра, если ты сова). Впиши только цифру (0-23), например \"8\" значит, что здороваться я буду с тобой в 8:00.";

    $fishy -> updateCommand($chat_id, "greet", 0, "{}");

    $bot->sendMessage($chat_id, $answer, "Markdown");
});

$bot->command('sum', function ($message) use ($bot) {
	$fishy = new Fishy();
	$fishy -> connect_db(HOST, USER, PASS, DB);

	$chat_id = $message->getChat()->getId();

	$answer = "Впиши время в которое я буду подводить итоги, после которых ты можешь идти спать или отдыхать. Впиши только цифру (0-23), например \"22\" значит, что подводить итоги я буду в 22:00.";

    $fishy -> updateCommand($chat_id, "sum", 0, "{}");

    $bot->sendMessage($chat_id, $answer, "Markdown");
});







$bot->run();

?>
