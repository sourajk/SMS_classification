#!/usr/bin/php

<?php

	date_default_timezone_set('Asia/Kolkata');

	if ($argc > 1 ) 		
		$limit2=$argv[1];
	else 
		$limit2=100;

	if($argc > 2)
		$limit=$argv[2];
	else
		$limit=400;

	$DATABASE_NAME="sms";
    $DATABASE_DATASET="dataset";  
    $DATABASE_BODY="body";  

    $db = mysql_connect('localhost:/tmp/mysql.sock', "p31", "password");
    mysql_select_db($DATABASE_NAME);

    $categories=array('Personal','Finance','Promotional','Updates','Other'); 

	//Get number of messages in each category
	$res = mysql_query("select category, count(*) from dataset group by category order by category;");
	//Store size
	$size=array();
	while($line = mysql_fetch_row($res))
		$size[$line[0]]= $line[1];

	//Get total number of messages
	$N = array_sum($size);

	//Get $limit most occouring words from each category
	$res = mysql_query("(select word from body b,dataset d where d.type=1 and b.date_sent=d.date_sent and b.addr=d.addr and category=1 group by word order by count(*) DESC LIMIT $limit) UNION
(select word from body b,dataset d where d.type=1 and b.date_sent=d.date_sent and b.addr=d.addr and category=2 group by word order by count(*) DESC LIMIT $limit) UNION
(select word from body b,dataset d where d.type=1 and b.date_sent=d.date_sent and b.addr=d.addr and category=3 group by word order by count(*) DESC LIMIT $limit) UNION
(select word from body b,dataset d where d.type=1 and b.date_sent=d.date_sent and b.addr=d.addr and category=4 group by word order by count(*) DESC LIMIT $limit);");

	$word=array();
	//Occourance in each category
	$occourance=array();
	$tfidf=array();
	$count=-1;

	//For every word inthe list do tf-idf for every category
	while($line = mysql_fetch_row($res))
	{
		$word[++$count] = $line[0];
		$res1=mysql_query("select word,count(*) from body where word='$line[0]';");
		$line1 = mysql_fetch_row($res1);
		$D = $line1[1];

		$idf=log10($N/$D);

		//Get occourance for each category
		$res1=mysql_query("select d1.category,count(*) from body b1,dataset d1 where d1.addr=b1.addr and d1.date_sent=b1.date_sent and b1.word='$word[$count]' group by d1.category;");

		$tfidf[$count] = array('',0,0,0,0,0);
		$occourance[$count] = array('',0,0,0,0,0);
		$tfidf[$count][0]= $word[$count];
		$occourance[$count][0]= $word[$count];

		//Calculate tf-idf for every category and occourance
		while($line1 = mysql_fetch_row($res1))
		{
			$occourance[$count][$line1[0]]= $line1[1];
			$tfidf[$count][$line1[0]]= ($line1[1]/$size[$line1[0]])*$idf; //////////////////////////////////////
		}	
	}




	$count2=-1;
	$score=array();

	//print_r(iness);

	//Limiter
	for($i=1;$i<=4;$i++)
	{
		for($j=0;$j<$count;$j++)
		{
			usort($tfidf, function($a, $b) use ($i) {
			    return $b[$i]>$a[$i];
			});
		}

		for($j=0;$j<$limit2;$j++)
		{
			$score[++$count2] = $tfidf[$j];
			//foreach ($score[$count2] as $value)
			//	echo "$value,";
			//echo "\n";
	
		}
		//echo "\n";
	}



	//Sort by name and remove duplicates
	usort($score, function($a, $b) {
			    return strcmp($a[0],$b[0]);
			});

	$score = array_unique($score, SORT_REGULAR);

	//foreach ($score as $value) 
	//	echo "\n$value[0],$value[1],$value[2],$value[3],$value[4]";
	

	//Write to file tf-idf and occourance for each category
	//$file_all=fopen('outcomes.csv','wb');
	$file_tfidf=fopen('tfidf.csv','wb');
	//$file_occ=fopen('occourance.csv','wb');

	$header=array('Word','Personal','Finance','Promotional','Updates','Other');
	fputcsv($file_tfidf, $header);
	foreach ($score as $fields) 
		fputcsv($file_tfidf, $fields);
/*
	fputcsv($file_occ, $header);
	foreach ($occourance as $fields) 
		fputcsv($file_occ, $fields);

*/

/*
	//Outcomes.csv
	$header=array('Word','Personal','Finance','Promotional','Updates','Other','Word','Personal','Finance','Promotional','Updates','Other');
	fputcsv($file_all, $header);
	for($i=0;$i<sizeof($tfidf);$i++)
	{
		$arr=array_merge($tfidf[$i],$occourance[$i]);
		fputcsv($file_all, $arr);
	}


*/

	//fclose($file_all);
	fclose($file_tfidf);
	//fclose($file_occ);



	//Choose values by sorting by tf-idf
	//Get $occourance/$size and get naive beyes values for 

	echo "WL - $limit2, RL - $limit\t";

	//echo "tfidf.php: tfidf.csv generated!\n";

?>