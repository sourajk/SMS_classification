#!/usr/bin/perl
use strict;

use DBI;
# also the module used below is required**
use XML::XPath;
my $dbh = DBI->connect('dbi:mysql:sms', 'root', 'root@123') || die $DBI::errstr;
my $xp = XML::XPath->new(filename => 'sou.xml');

my $sth = $dbh->prepare(qq{INSERT INTO dataset VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)});
# ?-->place holders so please keep away from them
foreach my $row ($xp->findnodes('/smses/sms')) {
		my $addr = $row->find('@address')->string_value;
		my $date = $row->find('@readable_date')->string_value;
	        my $con = $row->find('@contact_name')->string_value;
	        my $body = $row->find('@body')->string_value;
		$sth->execute($addr, $date, $con, $body,1,1,1,1,1) || die $DBI::errstr;
	}
$dbh->disconnect();