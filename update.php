<?php
// uso GDRIVE per deterimare dal portale CKAN Regionale l'ultimo CSV aggiornato e lo salvo in locale sul server ogni mezzanotte
$indirizzo ="https://docs.google.com/spreadsheets/d/1xqH_h2HHtOJO2_F1Glxbqh5kXpu9_XCPauuLHUS3j9k/export?format=csv&gid=1033778007&single=true";
$inizio=1;
$homepage ="";
//  echo $url;
$csv1 = array_map('str_getcsv', file($indirizzo));
	$url =$csv1[0][0];

  $homepage1 = file_get_contents($url);
	$homepage1=str_replace(",",".",$homepage1); //le lat e lon hanno la , e quindi metto il .
  $homepage1=str_replace(";",",",$homepage1); // concerto il CSV da separatore ; a ,

  //echo $homepage1;
  $file = '/usr/www/piersoft/viaggiareinpugliabot/db/luoghi.csv';

// scrivo il contenuto sul file locale.
  file_put_contents($file, $homepage1);
echo "finito";
?>
