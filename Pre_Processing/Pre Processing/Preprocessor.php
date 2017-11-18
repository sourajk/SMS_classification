#!/usr/bin/php

<?php


/*
	Input-CSV file with
	Header - 'generated',date, Number of messages
	Successive lines - addr,date_sent,contact_name,body,tagged_field

	Output - Save to database
	-----------------------------------------------------------------

	Pre-Processor tasks-
	1) Tokenization and non-aplha neumeric removal
	2) Stemming 
	3) Stop-word removal
	4) Upload to database
*/

	include 'stemmer.php';

	if ($argc > 1 ) {		
		$test=$argv[1];
	}
	else 
		$test=30;

	if ($argc > 2 ) {		
		$file_name=$argv[2];
	}
	else 
		$file_name="generated.csv";

	$file=fopen($file_name,'r');

	//Check header
	$line=fgetcsv($file);
	if(strcmp($line[0],"generated")!=0)
	{				
		echo "\nFile not of generated type!\n";
		return;
	}
	if(strcmp($line[1],"success")!=0)
	{
		echo "\nFile contains errors!\n";
		return;
	}




   	//Define database variables
   	$DATABASE_NAME="sms";
   	$DATABASE_DATASET="dataset";
   	$DATABASE_BODY="body";  

	$db = mysql_connect('localhost:/tmp/mysql.sock', "p31", "password");
    mysql_select_db($DATABASE_NAME); 



    $delimiters = array(' ',':','.',',',';','-','(',')','[',']','?','!','\\','/','#','%',);

    //Clear dataset and body from database;

    mysql_query("delete from body;");
    mysql_query("delete from dataset;");

	while(!feof($file))
	{
		$line=fgetcsv($file);
		$body=$line[3];

		$body = strtolower($body);
		//echo "\n$body\n";



		//1) Tokenisation - 1 point - Divya
		/*
			Input - $body //Message body
			Output - $words //Array containing words with only alphabets 
		*/
		$body = str_replace('(s)', ' ', $body);
		$body = str_replace('[s]', ' ', $body);

		$words = preg_split('/[^a-zA-Z]/i', $body);
		//$words=explodeX($delimiters, $body );
		$words = array_filter($words);
		$words = str_replace('\'', '', $words);
		
		//echo "\nAfter tokenization - \n";
		$words=array_values($words);
		//print_r($words);
        

		//2) Stemming - 2 points - Sumeera
		/*
			Input - $words 
			Output - $words //Array containing words after stemming
			Example - looks,looking,looked must all be changed to look
		*/
		$stemwords=Array();
		foreach($words as $word)
			$stemwords[] = PorterStemmer::stem($word);
		$words=$stemwords;
		//echo "\nAfter stemming - \n";
		//print_r($words);

		//3) Stop word removal - 2 points - Souraj
		/*
			Input - $words 
			Output - $words //Smaller array without stop words
			Example of words to be removed - a,the,in,on, etc Use databse found online
		*/
		//$words=removestopWords($words);
		
		//echo "\nAfter stop word removal - \n";
		$words=array_values($words);
        //print_r($words);

		//4) Upload to database - 4 points - Kanishth
		/*
			Input - $words 
			Output - Upload give line with all fields line[0],line[1],line[2],line[4] and $words to a database
				Database needs to be designed with no multi- value fields.
				Occourance of each word must also be stored.
			Write query to get:-
				All data for all messages in different rows.
				All data for given message in one row.
				All data for messages in given category given by category field.
		*/
			
		//Generate file syntax - addr,date_sent,contact_name,body,tagged_field
		$addr=$line[0];
		$date=$line[1];
		$contact_name=$line[2];
		$category=$line[4];

		//get random variable
		$rnd=rand(0,100);
		$type=($rnd>=$test)?1:2;

		//Insert to database
		$res = mysql_query("INSERT INTO $DATABASE_DATASET VALUES('$addr',$date,'$contact_name',$category,$type)");
		if($res!=1)
		{
			//To know which dropped!
			echo "\nINSERT INTO $DATABASE_DATASET VALUES('$addr',$date,'$contact_name',$category,$type)";
			//$dropped++;
		}
		else
		{
			asort($words);	
			$words=array_values($words);
			$counts = array_count_values($words);	


			for ($i=0; $i<sizeof($words); $i++) 
			{			
				$word=$words[$i];
				$count = $counts[$word];
				$i=$i+$count;

				//echo $count[$word];
				$res = mysql_query("INSERT INTO $DATABASE_BODY VALUES('$addr',$date,'$word',$count)");
				if($res!=1)
				{
					echo "\n$i - INSERT INTO $DATABASE_BODY VALUES('$addr',$date,'$word',$count)";
				}
				else
					;//$size++;
			}
		}	

	}
	
	mysql_close($db);
	fclose($file);
	echo "Test % - $test\t";

	function explodeX( $delimiters, $string ) //Divya 
    {
		return explode( chr(1), str_replace( $delimiters, chr(1), $string ) );
    }
	function removestopWords($input) //Souraj
	{
			$stopwords = array("a", "about", "above", "across", "after", "afterwards", "again", "against", "all", "almost", "alone", "along", "already", "also","although","always","am","among", "amongst", "amoungst", "amount",  "an", "and", "another", "any","anyhow","anyone","anything","anyway", "anywhere", "are", "around", "as",  "at", "back","be","became", "because","become","becomes", "becoming", "been", "before", "beforehand", "behind", "being", "below", "beside", "besides", "between", "beyond", "bill", "both", "bottom","but", "by", "call", "can", "cannot", "can\'t", "co", "con", "could", "couldn\'t", "cry", "de", "describe", "detail", "do", "done", "down", "due", "during", "each", "eg", "eight", "either", "eleven","else", "elsewhere", "empty", "enough", "etc", "even", "ever", "every", "everyone", "everything", "everywhere", "except", "few", "fifteen", "fify", "fill", "find", "fire", "first", "five", "for", "former", "formerly", "forty", "found", "four", "from", "front", "full", "further", "get", "give", "go", "had", "has", "hasn\'t", "have", "he", "hence", "her", "here", "hereafter", "hereby", "herein", "hereupon", "hers", "herself", "him", "himself", "his", "how", "however", "hundred", "ie", "if", "in", "inc", "indeed", "interest", "into", "is", "it", "its", "itself", "keep", "last", "latter", "latterly", "least", "less", "ltd", "made", "many", "may", "me", "meanwhile", "might", "mill", "mine", "more", "moreover", "most", "mostly", "move", "much", "must", "my", "myself", "name", "namely", "neither", "never", "nevertheless", "next", "nine", "no", "nobody", "none", "noone", "nor", "not", "nothing", "now", "nowhere", "of", "off", "often", "on", "once", "one", "only", "onto", "or", "other", "others", "otherwise", "our", "ours", "ourselves", "out", "over", "own","part", "per", "perhaps", "please", "put", "rather", "re", "same", "see", "seem", "seemed", "seeming", "seems", "serious", "several", "she", "should", "show", "side", "since", "sincere", "six", "sixty", "so", "some", "somehow", "someone", "something", "sometime", "sometimes", "somewhere", "still", "such", "system", "take", "ten", "than", "that", "the", "their", "them", "themselves", "then", "thence", "there", "thereafter", "thereby", "therefore", "therein", "thereupon", "these", "they", "thick", "thin", "third", "this", "those", "though", "three", "through", "throughout", "thru", "thus", "to", "together", "too", "top", "toward", "towards", "twelve", "twenty", "two", "un", "under", "until", "up", "upon", "us", "very", "via", "was", "we", "well", "were", "what", "whatever", "when", "whence", "whenever", "where", "whereafter", "whereas", "whereby", "wherein", "whereupon", "wherever", "whether", "which", "while", "whither", "who", "whoever", "whole", "whom", "whose", "why", "will", "with", "within", "without", "would","wouldn\'t","yet", "you", "your", "yours", "yourself", "yourselves", "the");
		$input = array_diff($input,$stopwords);
		return $input;
	}
?>