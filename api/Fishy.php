<?php

	class Fishy {
		public static $mysqli;
		private static $to_zero_gmt = "-3";
		public function connect_db($host, $user, $pass, $db){
			self::$mysqli = new mysqli($host, $user, $pass, $db);

			if (mysqli_connect_errno()) {
			    return 0;
			}
			return 1;
		}

		public function disconnect_db(){
			self::$mysqli -> close();
		}

		public function addUser($user, $chat_id){

			$query = "INSERT INTO `users` (`name`, `chat_id`) VALUES (?, ?)";
			$stmt = self::$mysqli->prepare($query);
			$stmt -> bind_param("si", $user, $chat_id);
			$result = $stmt -> execute();
			$stmt -> close();
			if($result){
				$query = "INSERT INTO `commands` (`chat_id`) VALUES (?)";
				$stmt = self::$mysqli->prepare($query);
				$stmt -> bind_param("i", $chat_id);
				$result = $stmt -> execute();
				$stmt -> close();
				if($result){
					$query = "INSERT INTO `settings` (`chat_id`) VALUES (?)";
					$stmt = self::$mysqli->prepare($query);
					$stmt -> bind_param("i", $chat_id);
					$result = $stmt -> execute();
					$stmt -> close();
					
					if($result)
					return true;	
				}

			} 
			
		}

		public function addTodo($date, $description, $chat_id){
			$query = "INSERT INTO `todo` (`date`, `chat_id`, `description`) VALUES (?, ?, ?)";
			$stmt = self::$mysqli->prepare($query);
			$stmt -> bind_param("sis", $date, $chat_id, $description);
			$result = $stmt -> execute();
			$stmt -> close();
			if($result){
				return "Я успешно записала задание \"$description\" на $date";
			} 
			
		}

		public function removeTodo($date, $description, $chat_id){
			$query = "DELETE FROM `todo` WHERE `chat_id` = ? AND `date` = ? AND `description` = ?";
			$stmt = self::$mysqli->prepare($query);
			$stmt -> bind_param("iss", $chat_id, $date, $description);
			$result = $stmt -> execute();
			$stmt -> close();
			if($result){
				return "Я успешно удалила задание \"$description\" из $date\n\n";
			} 
			
		}

		public function updateCommand($chat_id, $command, $progress, $json){
			$query = "UPDATE `commands` SET `command_name` = ?, `progress` = ?, `json` = ? WHERE `chat_id` = ?";
			$stmt = self::$mysqli->prepare($query);
			$stmt -> bind_param("sisi", $command, $progress, $json, $chat_id);
			$result = $stmt -> execute();
			$stmt -> close();

			if($result){
				return true;
			}
		}

		public function completeTask($date, $description, $chat_id){
			$query = "UPDATE `todo` SET `status` = 1 WHERE `chat_id` = ? AND `description` = ? AND `date` = ?";
			$stmt = self::$mysqli->prepare($query);
			$stmt -> bind_param("iss", $chat_id, $description, $date);
			$result = $stmt -> execute();
			$stmt -> close();

			if($result){
				return "Поздравляю! \"$description\" ($date) выполнен!\n";
			}
		}

		public function getCommand($chat_id){
			$query = "SELECT * FROM `commands` WHERE `chat_id` = ?";
			$stmt = self::$mysqli->prepare($query);
			$stmt -> bind_param("i", $chat_id);
			$result = $stmt -> execute();

			$res = $stmt -> get_result();
			$row = $res -> fetch_assoc();
			$stmt -> close();

			if($result){
				return $row;
			}
		}

		public function getTodo($chat_id, $date){
			$query = "SELECT * FROM `todo` WHERE `chat_id` = ? AND `date` = ?";
			$stmt = self::$mysqli->prepare($query);
			$stmt -> bind_param("is", $chat_id, $date);
			$result = $stmt -> execute();

       		$res = $stmt -> get_result();


			$i = 0;
			while ( $row = $res -> fetch_assoc()) {
			    $rows[$i] = $row;
			    $i++;
			}       		
			$stmt -> close();
			if($rows){
					return $rows;
			} 
			
		}

		public function checkdate($date, $chat_id){
			$arrgmt = self::getGmt($chat_id);
			$gmt = $arrgmt['gmt'];
			if( strcasecmp($date, "сегодня") == 0 || strcasecmp($date, "today") == 0){
				$date = date("d.m.Y" , strtotime(self::$to_zero_gmt . " hour $gmt hour"));
			} else if (strcasecmp($date, "завтра") == 0 || strcasecmp($date, "tomorrow") == 0) {
				$date = date("d.m.Y", strtotime('+1 day' . self::$to_zero_gmt . " hour $gmt hour"));
			}
			else if (strcasecmp($date, "after_tomorrow") == 0 || strcasecmp($date, "послезавтра") == 0) {
				$date = date("d.m.Y", strtotime('+2 day' . self::$to_zero_gmt . " hour $gmt hour"));
			}
			else if(!preg_match("/^\s*(3[01]|[12][0-9]|0?[1-9])\.(1[012]|0?[1-9])\.((?:20)18)\s*$/", $date) 
				|| (strtotime(self::$to_zero_gmt . " hour $gmt hour")-(60*60*24)) > strtotime($date)){
				return date("d.m.Y" , strtotime(self::$to_zero_gmt . " hour $gmt hour"));
			}

			return $date;
		}

		public function addDay($date, $day){
			$date = strtotime($date);
			$date = $date + (60*60*24)*$day;
			return date("d.m.Y", $date);
		}

		public function getUsers(){
			$query = "SELECT * FROM `users`";
			$stmt = self::$mysqli->prepare($query);
			$result = $stmt -> execute();	

       		$res = $stmt -> get_result();

			$i = 0;
			while ( $row = $res -> fetch_assoc()) {
			    $rows[$i] = $row;
			    $i++;
			}       		
			$stmt -> close();
			if($rows){
					return $rows;
			} 		
		}

		public function moveTomorrow($chat_id){
			$today = self::checkdate("today", $chat_id);
			$tomorrow = self::checkdate("tomorrow", $chat_id);

			$query = "UPDATE `todo` SET `date` = ? WHERE `chat_id` = ? AND `date` = ? AND `status` = 0";
			$stmt = self::$mysqli->prepare($query);
			$stmt -> bind_param("sis", $tomorrow, $chat_id, $today);
			$result = $stmt -> execute();
			$stmt -> close();

			if($result){
				return "Невыполненные сегодняшние цели успешно перенесены на завтра.\n";
			}
		}

		public function moveToday($chat_id){
			$today = self::checkdate("today", $chat_id);
			$yesterday = self::addDay($today, -1);

			$query = "UPDATE `todo` SET `date` = ? WHERE `chat_id` = ? AND `date` = ? AND `status` = 0";
			$stmt = self::$mysqli->prepare($query);
			$stmt -> bind_param("sis", $today, $chat_id, $yesterday);
			$result = $stmt -> execute();
			$stmt -> close();

			if($result){
				return "Невыполненные вчерашние цели успешно перенесены на сегодня.\n";
			}
		}

		public function checkYesterday($today){
			$today = self::addDay($today, -1);
			$query = "SELECT `status` FROM `todo` WHERE `date` = ? AND `status` = 0";
			$stmt = self::$mysqli->prepare($query);
			$stmt -> bind_param("s", $today);
			$result = $stmt -> execute();	

       		$res = $stmt -> get_result();
       		$row = $res -> fetch_assoc();

			if($row){
				return 1;
			} else return 0;
		}

		public function setGmt($chat_id, $gmt){
			$query = "UPDATE `settings` SET `gmt` = ? WHERE `chat_id` = ?";
			$stmt = self::$mysqli->prepare($query);
			$stmt -> bind_param("ss", $gmt, $chat_id);
			$result = $stmt -> execute();
			$stmt -> close();

			if($result){
				return "Я успешно поставила тебе GMT $gmt\n";
			}
		}

		public function getGmt($chat_id){
			$query = "SELECT `gmt` FROM `settings` WHERE `chat_id` = ?";
			$stmt = self::$mysqli->prepare($query);
			$stmt -> bind_param("i", $chat_id);
			$result = $stmt -> execute();
			
			$res = $stmt -> get_result();
			$row = $res -> fetch_assoc();
			$stmt -> close();

			if($result){
				return $row;
			}
		}

		public function clearOld(){
			$date = date("d.m.Y" , strtotime(self::$to_zero_gmt . " hour -2 day"));

			$query = "DELETE FROM `todo` WHERE `date` = ?";
			$stmt = self::$mysqli->prepare($query);
			$stmt -> bind_param("s", $date);
			$result = $stmt -> execute();
			$stmt -> close();
			if($result){
				return true;
			} 
		}

		public function getHour($chat_id){
			$arrgmt = self::getGmt($chat_id);
			$gmt = $arrgmt['gmt'];
			return date("H" , strtotime(self::$to_zero_gmt . " hour $gmt hour"));
		}

		public function getSettings($chat_id){
			$query = "SELECT * FROM `settings` WHERE `chat_id` = ?";
			$stmt = self::$mysqli->prepare($query);
			$stmt -> bind_param("i", $chat_id);
			$result = $stmt -> execute();
			
			$res = $stmt -> get_result();
			$row = $res -> fetch_assoc();
			$stmt -> close();

			if($result){
				return $row;
			}
		}

		public function setGreet($chat_id, $greet){
			$query = "UPDATE `settings` SET `greet_time` = ? WHERE `chat_id` = ?";
			$stmt = self::$mysqli->prepare($query);
			$stmt -> bind_param("ss", $greet, $chat_id);
			$result = $stmt -> execute();
			$stmt -> close();

			if($result){
				return "Отлично! В $greet:00 я тебя поприветствую!\n";
			}
		}

		public function setSum($chat_id, $sum){
			$query = "UPDATE `settings` SET `sum_time` = ? WHERE `chat_id` = ?";
			$stmt = self::$mysqli->prepare($query);
			$stmt -> bind_param("ss", $sum, $chat_id);
			$result = $stmt -> execute();
			$stmt -> close();

			if($result){
				return "Отлично! В $sum:00 я подведу итоги твоего дня.\n";
			}
		}
	}
?>	