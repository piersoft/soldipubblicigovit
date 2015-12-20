<?php
/**
* Telegram Bot example for Soldipubblici.gov.it Lic. CC-BY 3.0 http://creativecommons.org/licenses/by/3.0/it/legalcode
* @author Francesco Piero Paolicelli @piersoft
*/
include("Telegram.php");

class mainloop{
const MAX_LENGTH = 4096;
function start($telegram,$update)
{

	date_default_timezone_set('Europe/Rome');
	$today = date("Y-m-d H:i:s");

	$text = $update["message"] ["text"];
	$chat_id = $update["message"] ["chat"]["id"];
	$user_id=$update["message"]["from"]["id"];
	$location=$update["message"]["location"];
	$reply_to_msg=$update["message"]["reply_to_message"];

	$this->shell($telegram,$text,$chat_id,$user_id,$location,$reply_to_msg);
	$db = NULL;

}

 function shell($telegram,$text,$chat_id,$user_id,$location,$reply_to_msg)
{
	date_default_timezone_set('Europe/Rome');
	$today = date("Y-m-d H:i:s");

	if ($text == "/start" || $text == "Informazioni") {
		$reply = "Benvenuto. Per ricercare le spese di un Ente, devi prima ricavare il Codice Ente e poi puoi ricercare per parola chiave la spesa. Segui le istruzioni delle sezioni Ricerca e Ente. Verrà interrogato il DataBase openData utilizzabile con licenza CC-BY 3.0 (http://creativecommons.org/licenses/by/3.0/it/legalcode) presente su http://soldipubblici.gov.it/it/home . In qualsiasi momento scrivendo /start ti ripeterò questo messaggio di benvenuto.\nQuesto bot, non ufficiale e non collegato con il sito del Governo Soldipubblici.gov.it, è stato realizzato da @piersoft e potete migliorare il codice sorgente con licenza MIT che trovate su https://github.com/piersoft/soldipubblicigovit.";
		$content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
		$telegram->sendMessage($content);
		$log=$today. ",new chat started," .$chat_id. "\n";
		file_put_contents(dirname(__FILE__).'/./db/telegram.log', $log, FILE_APPEND | LOCK_EX);

		$this->create_keyboard_temp($telegram,$chat_id);
		exit;
		}
		elseif ($text == "Ente" || $text == "/ente") {
			$reply = "Digita direttamente il nome dell'Ente per conoscerne il Codice Ente che ti servirà per la ricerca delle spese. Esempio: Comune di Prato";
			$content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
			$telegram->sendMessage($content);
			$log=$today. ",Ente," .$chat_id. "\n";
			file_put_contents(dirname(__FILE__).'/./db/telegram.log', $log, FILE_APPEND | LOCK_EX);
			$this->create_keyboard_temp($telegram,$chat_id);

			exit;
			}
			elseif ($text == "Ricerca"|| $text == "/ricerca") {
				$reply = "Scrivi in ordine: %codiceente ricavato dalla ricerca dell'Ente e poi la spesa da cercare anteponendo il carattere ?, ad esempio: %000705530?Spese postali per le Spese Postali inerenti il Comune di Lecce";
				$content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
				$telegram->sendMessage($content);
				$log=$today. ",ricerca," .$chat_id. "\n";
				file_put_contents(dirname(__FILE__).'/./db/telegram.log', $log, FILE_APPEND | LOCK_EX);
				$this->create_keyboard_temp($telegram,$chat_id);

				exit;
			}

		elseif(strpos($text,'/') === false){
			function extractString($string, $start, $end) {
					$string = " ".$string;
					$ini = strpos($string, $start);
					if ($ini == 0) return "";
					$ini += strlen($start);
					$len = strpos($string, $end, $ini) - $ini;
					return substr($string, $ini, $len);
			}
			$string=0;
			$img = curl_file_create('soldipubblici.png','image/png');
			$contentp = array('chat_id' => $chat_id, 'photo' => $img);
			$telegram->sendPhoto($contentp);
			if(strpos($text,'%') !== false){
  			$codiceente=extractString($text,"%","?");
				$text=str_replace($codiceente,"",$text);
				$text=str_replace("%","",$text);
				$text=str_replace("?","",$text);
				$location="Sto interrogando Soldipubblici.gov.it per\ncodice ente: ".$codiceente."\ne per: ".$text;
				$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
				$telegram->sendMessage($content);

			$where=utf8_decode($text);
		 	$where=str_replace("?","",$where);
		 	$where=str_replace(" ","%20",$where);
		 	extract($_POST);
		 	$url = 'http://soldipubblici.gov.it/it/ricerca';
		 	$ch = curl_init();
		 	$file = fopen('db/spese.json', 'w+'); //da decommentare se si vuole il file locale
		 	curl_setopt($ch,CURLOPT_URL, $url);
		 	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded; charset=UTF-8','Accept: Application/json','X-Requested-With: XMLHttpRequest','Content-Type: application/octet-stream','Content-Type: application/download','Content-Type: application/force-download','Content-Transfer-Encoding: binary '));
		 	curl_setopt($ch,CURLOPT_POSTFIELDS, 'codicecomparto=PRO&codiceente='.$codiceente.'&cosa='.$where);
		 	curl_setopt($ch, CURLOPT_FILE, $file);
		 	curl_exec($ch);
		 	curl_close($ch);

		 	$json_string = file_get_contents("db/spese.json");
		 	$parsed_json = json_decode($json_string);
		 		//var_dump(  $parsed_json); // debug
		 	$count = 0;
		 	foreach($parsed_json->{'data'} as $data=>$csv1){
		 			 $count = $count+1;
		 		}
				if ($count ==0){
					$location="Nessun risultato trovato";
					$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
					$telegram->sendMessage($content);
					$this->create_keyboard_temp($telegram,$chat_id);
					exit;
				}
		 		$temp_c1="";
		 		for ($i=0;$i<$count;$i++){
		 		$temp_c1 .="\n\n";
		 		$mese=  substr_replace($parsed_json->{'data'}[$i]->{'imp_uscite_att'}, ",", -2, 0);
		 		$annoprecedente=substr_replace($parsed_json->{'data'}[$i]->{'importo_2014'}, ",", -2, 0);
		 		$annoincorso=substr_replace($parsed_json->{'data'}[$i]->{'importo_2015'}, ",", -2, 0);
		 		$temp_c1 .= "Ricerca per: ".$parsed_json->{'data'}[$i]->{'ricerca'}."\nTrovata la voce: ".$parsed_json->{'data'}[$i]->{'descrizione_codice'}."\nCodice Siope: ".$parsed_json->{'data'}[$i]->{'codice_siope'}."\nNel periodo ".$parsed_json->{'data'}[$i]->{'periodo'}."/".$parsed_json->{'data'}[$i]->{'anno'}." spesi: ".$mese."€\nNel 2014 sono stati spesi: ".$annoprecedente."€\nIl progressivo 2015 è ".$annoincorso."€";
		 		$temp_c1 .="\n";


		 	}
				$chunks = str_split($temp_c1, self::MAX_LENGTH);
				foreach($chunks as $chunk) {
						$content = array('chat_id' => $chat_id, 'text' => $chunk,'disable_web_page_preview'=>true);
						$telegram->sendMessage($content);
				}
				$this->create_keyboard_temp($telegram,$chat_id);
					$log=$today. ",spese," .$chat_id. "\n";
					file_put_contents(dirname(__FILE__).'/./db/telegram.log', $log, FILE_APPEND | LOCK_EX);

				exit;
			}else{
				$where=$text;
				$where=utf8_decode($where);

				$where=str_replace("?","",$where);
				$where=str_replace(" ","%20",$where);
				extract($_POST);
				$url = 'http://soldipubblici.gov.it/it/chi/search/'.$where;
				$ch = curl_init();
				$file = fopen('db/entespese.json', 'w+'); //da decommentare se si vuole il file locale
				curl_setopt($ch,CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded; charset=UTF-8','Accept: Application/json','X-Requested-With: XMLHttpRequest','Content-Type: application/octet-stream','Content-Type: application/download','Content-Type: application/force-download','Content-Transfer-Encoding: binary '));
				//curl_setopt($ch,CURLOPT_POSTFIELDS, $where);
				curl_setopt($ch, CURLOPT_FILE, $file);
				curl_exec($ch);
				curl_close($ch);

					$json_string = file_get_contents("db/entespese.json");
					$parsed_json = json_decode($json_string);
					//  var_dump($parsed_json[0]->{'ripartizione_geografica'}); // debug
					$count = 0;
					foreach($parsed_json as $data=>$csv1){
							 $count = $count+1;
						}
						if ($count ==0){
							$location="Nessun risultato trovato";
							$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
							$telegram->sendMessage($content);
							$this->create_keyboard_temp($telegram,$chat_id);
							exit;
						}
				//echo $count;
						$temp_c1="";
							$temp_c1 .="\nEnte: ".$parsed_json[0]->{'descrizione_ente'};
							$temp_c1 .="\nRicerca per comparto: ".$parsed_json[0]->{'codice_sottocomparto'};
							$temp_c1 .="\nNumero abitanti: ".$parsed_json[0]->{'numero_abitanti'};
							$temp_c1 .="\nCodice Ente: ".$parsed_json[0]->{'codice_ente'};
							$chunks = str_split($temp_c1, self::MAX_LENGTH);
							foreach($chunks as $chunk) {
									$content = array('chat_id' => $chat_id, 'text' => $chunk,'disable_web_page_preview'=>true);
									$telegram->sendMessage($content);
							}
							$location="ora puoi interrogare il database digitando ad esempio: %".$parsed_json[0]->{'codice_ente'}."?spese postali ";
							$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
							$telegram->sendMessage($content);
							$this->create_keyboard_temp($telegram,$chat_id);
							$log=$today. ",codice ente," .$chat_id. "\n";
							file_put_contents(dirname(__FILE__).'/./db/telegram.log', $log, FILE_APPEND | LOCK_EX);

							exit;
			}


		}


		$this->create_keyboard_temp($telegram,$chat_id);

}



	function create_keyboard_temp($telegram, $chat_id)
	 {
			 $option = array(["Ente","Ricerca"],["Informazioni"]);
			 $keyb = $telegram->buildKeyBoard($option, $onetime=false);
			 $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "");
			 $telegram->sendMessage($content);
	 }

}

?>
