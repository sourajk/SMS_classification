#!/usr/bin/php

<?php

	date_default_timezone_set('Asia/Kolkata');

/*
	options-
	r - upload raw
	t - upload tagged
	file_name - path of tagged file to be uploaded.

	g - generate csv with tagged data

	Line 1 of every csv file will conatain a tag indicating file type
	upload.csv - upload_raw/upload_tagged,success/failure,code

*/

	//Check validity of options
	if ($argc < 3 ) {
		echo "Usage : ./Server.php user_name g|r|t file_name\n";
		exit();
	}

	if($argc<4)
		$file_name="input.csv";
	else	
		$file_name=$argv[3];


    //Defining constants
    //$USN_LIST={"d","sk","k","sb"}
    $DATABASE_NAME="sms";
    $DATABASE_TRANSACTION="transaction";
    $DATABASE_TABLE_NAME="raw";  
    $DATABASE_LOG="log";  

    //Define database variables
	$db = mysql_connect('localhost:/tmp/mysql.sock', "p31", "password");
    mysql_select_db($DATABASE_NAME);

    $usn=$argv[1];

    if($argv[2]=='r')
    	upload_raw($file_name,$usn);
    else if($argv[2]=='t')
    	upload_tagged($file_name,$usn);
    else if($argv[2]=='g')
    	generate_csv();


	mysql_close($db);
    echo "Server.php: All done!";


	function upload_tagged($path, $usn) //TODO
	{ 
		global $db, $DATABASE_NAME, $DATABASE_TABLE_NAME,$DATABASE_LOG,$DATABASE_TRANSACTION;
		$file=fopen($path,'r');

		$line=fgetcsv($file);
		
		//Check file type
		if(strcmp($line[0],"upload_tagged")!=0)
		{	
			echo "\nFile not of upload type!\n";
			return;
		}
		if(strcmp($line[1],"success")!=0)
		{
			echo "\nFile contains errors!\n";
			return;
		}
		$code=$line[2];
		
		$res = mysql_query("SELECT * FROM $DATABASE_LOG WHERE code='$code' and usn='$usn'");
		$a = mysql_fetch_row($res); 
		if($a[0]==$code)
		{
        	echo "\nAlready uploaded for given user!!\n";
			return;
    	}

    	$size=0;
    	$count=0;
    	$dropped=0;
		while(!feof($file))
		{
			$line=fgetcsv($file);
			switch ($line[4]) {
				case '1':
					$category='personal';
					break;
				case '2':
					$category='finance';
					break;
				case '3':
					$category='promo';
					break;
				case '4':
					$category='updates';
					break;
				case '5':
					$category='others';
					break;
				
				default:
					echo "\nInvalid category code!\n";
					//Write to log file
					break;
			}
			
			$addr=$line[0];
			$date_sent=substr($line[1],0,10); 

			//Prevent data loss from primary key clause.
			if($date_sent==0 || $date_sent=='0')
				$date_sent=++$count;

			$res = mysql_query("UPDATE $DATABASE_TABLE_NAME SET $category=$category+1,tagged=tagged+1 WHERE addr='$addr' and date_sent='$date_sent'");

			if($res==FALSE)
			{
				//To know which dropped!
				echo "\nUPDATE $DATABASE_TABLE_NAME SET $category=$category+1,tagged=tagged+1 WHERE addr='$addr' and date_sent='$date_sent'";
				$dropped++;
			}
			else
			{
				//Save transaction
				$res= mysql_query("INSERT INTO $DATABASE_TRANSACTION values($code,'$usn','$addr','$date_sent',$line[4])");
				if($res==FALSE)
				{
					$dropped++;
					echo "\nINSERT INTO $DATABASE_TRANSACTION values($code,'$usn','$addr','$date_sent',$line[4])";
				}
				else
					$size++;

			}
		}

		mysql_query("INSERT INTO $DATABASE_LOG values($code,'$usn',$size)");
		if($res!=1)
			echo "INSERT INTO $DATABASE_LOG values($code,'$usn',$size)";

		fclose($file);

		echo "\nTagged file uploaded to db!\nSize - $size\nDropped - $dropped";

	}

	function upload_raw($path,$usn) //TODO
	{ 
		global $db, $DATABASE_NAME, $DATABASE_TABLE_NAME,$DATABASE_LOG;

		$file=fopen($path,'r');

		$line=fgetcsv($file);

		
		//Check file type
		if(strcmp($line[0],"upload_raw")!=0)
		{	
			echo "\nFile not of upload type!\n";
			return;
		}
		if(strcmp($line[1],"success")!=0)
		{
			echo "\nFile contains errors!\n";
			return;
		}
		$code=$line[2];
		
		$res = mysql_query("SELECT * FROM $DATABASE_LOG where code=$code");
		$a = mysql_fetch_row($res); 
		if($a[0]==$code)
		{
        	echo "\nAlready uploaded!!\n";
			return;
    	}

		$size=0;
		$count=0;
		$dropped=0;
		while(!feof($file))
		{
			$line=fgetcsv($file);
			$addr=$line[0];
			$date_sent=substr($line[1],0,10); 
			$contact_name=$line[2];
			$body=$line[3];			

			//Prevent data loss from primary key clause.
			if($date_sent==0)
				$date_sent=++$count;

			$res = mysql_query("INSERT INTO $DATABASE_TABLE_NAME values('$addr',$date_sent,'$contact_name','$body',0,0,0,0,0,0)");

			if($res!=1)
			{
				//To know which dropped!
				echo "\n$res		INSERT INTO $DATABASE_TABLE_NAME values('$addr',$date_sent,'$contact_name','$body',0,0,0,0,0,0)";
				$dropped++;
			}
			else
				$size++;


		}
		mysql_query("INSERT INTO $DATABASE_LOG values ($code,'raw_$usn',$size)");
		fclose($file);

		echo "Raw file uploaded to db!\nSize - $size\nDropped - $dropped";

	}

	function generate_csv()
	{
		global $db, $DATABASE_NAME, $DATABASE_TABLE_NAME,$DATABASE_LOG;

		$res = mysql_query("SELECT * FROM $DATABASE_TABLE_NAME where ((finance>(others+promo+personal+updates) or promo>(others+finance+personal+updates) or personal>(finance+promo+updates+others)) or updates>(finance+promo+personal+others)) and tagged>=2;");
		
		$file=fopen('generated.csv','wb');

		$date1=date('d/m/y');
    	//Add info in to $allocated_info
    	$line=array('generated','success',$date1,(mysql_num_rows($res)),0); //Header for generated file

		fputcsv($file, $line);
		while($line = mysql_fetch_row($res))
		{
			$category=4;
			for($i=5;$i<=8;$i++)
				if($line[$i]>$line[$category])
					$category=$i;

			$category=$category-3;

			$l2=array($line[0],$line[1],$line[2],$line[3],$category); //addr,date_sent,contact_name,body,tagged_field
			fputcsv($file, $l2);
		}

		$stat = fstat($file);
		ftruncate($file, $stat['size']-1);

		fclose($file);

		echo "Server.php: generated.csv file generated!\n";

	}

?>